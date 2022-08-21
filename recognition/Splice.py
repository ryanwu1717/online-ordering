import re
import base64
import uuid
import imageio
import numpy as np
from PIL import Image,ImageOps
import cv2
import math
import pytesseract
import sys
import NO_recog
import Comp
from flask import Flask
from flask import jsonify
from flask import request
from flask import _request_ctx_stack
import pathlib
import glob, os
import TextRecog
import threading
import time
from os import walk
import os
import json
import ast
import requests
import PartCut
from scipy import ndimage
from pdf2image import convert_from_path,convert_from_bytes
#from fuzzywuzzy import fuzz
from thefuzz import fuzz
import pdfkit
from skimage import morphology as morphology_sk
from scipy.ndimage import morphology as morphology_sci
from scipy.ndimage import label
from torchvision import models,transforms
import torch.optim as optim
import torch.nn as nn
import torch
import torch.utils.data as data
from torch.autograd import Variable
import argparse
import string
import torch.backends.cudnn as cudnn
import torch.utils.data
import torch.nn.functional as torchF
import craft_utils
import imgproc
import file_utils
import merge
import merge2
from craft import CRAFT
from collections import OrderedDict
import keywordFinder
import noteFinder
import messageParser
import pdfSplit
import orderformParser
from orderParserV2 import orderParser
import OrderParserV3
import statistics
import keywordFinder_v2

from utilss import CTCLabelConverter, AttnLabelConverter
from dataset import RawDataset, AlignCollate, RawDatasetWithBB
from recmodel import Model as recModel

import pytesseract
sys.path.append("logo")
from logo import Logo
from component.component import Component


os.environ['KMP_DUPLICATE_LIB_OK']='True'
device = torch.device("cuda:0" if torch.cuda.is_available() else "cpu")

pathlib.Path(__file__).parent.absolute()
sys.argv = ['-f']

# logo = Logo()
# component = Component()


model = models.vgg11_bn(pretrained=False)
model.classifier[6] = nn.Linear(in_features=4096, out_features=2)
checkpoint = torch.load("best_checkpoint_3.pth", map_location=torch.device(device))
model.load_state_dict(checkpoint)
model.eval()

model2 = models.vgg11_bn(pretrained=False)
model2.classifier[6] = nn.Linear(in_features=4096, out_features=447)
checkpoint2 = torch.load("best_checkpoint_last_para.pth", map_location=torch.device(device))
model2.load_state_dict(checkpoint2)
model2.eval()
#model = model.to(device)
torch_size = 224
torch_mean = (0.5, 0.5, 0.5)
torch_std = (0.5, 0.5, 0.5)
preprocess = transforms.Compose([
                transforms.Resize(224),
                transforms.ToTensor(),
                transforms.Normalize(torch_mean, torch_std)
            ])

def copyStateDict(state_dict):
    if list(state_dict.keys())[0].startswith("module"):
        start_idx = 1
    else:
        start_idx = 0
    new_state_dict = OrderedDict()
    for k, v in state_dict.items():
        name = ".".join(k.split(".")[start_idx:])
        new_state_dict[name] = v
    return new_state_dict

model_textbox = CRAFT()
model_textbox.load_state_dict(copyStateDict(torch.load("craft_mlt_25k.pth", map_location=torch.device(device))))
if torch.cuda.is_available() :
	model_textbox = model_textbox.cuda()
model_textbox = torch.nn.DataParallel(model_textbox)
cudnn.benchmark = False
model_textbox.eval()
parser = argparse.ArgumentParser()

parser.add_argument('--image_folder', default="CutTexts", help='path to image_folder which contains text images')
parser.add_argument('--workers', type=int, help='number of data loading workers', default=4)
parser.add_argument('--batch_size', type=int, default=400, help='input batch size')
parser.add_argument('--saved_model', default="TPS-ResNet-BiLSTM-Attn.pth", help="path to saved_model to evaluation")
""" Data processing """
parser.add_argument('--batch_max_length', type=int, default=25, help='maximum-label-length')
parser.add_argument('--imgH', type=int, default=32, help='the height of the input image')
parser.add_argument('--imgW', type=int, default=100, help='the width of the input image')
parser.add_argument('--rgb', action='store_true', help='use rgb input')
parser.add_argument('--character', type=str, default='0123456789abcdefghijklmnopqrstuvwxyz', help='character label')
parser.add_argument('--sensitive', action='store_true', help='for sensitive character mode')
parser.add_argument('--PAD', action='store_true', help='whether to keep ratio then pad for image resize')
""" Model Architecture """
parser.add_argument('--Transformation', type=str, default="TPS", help='Transformation stage. None|TPS')
parser.add_argument('--FeatureExtraction', type=str, default="ResNet", help='FeatureExtraction stage. VGG|RCNN|ResNet')
parser.add_argument('--SequenceModeling', type=str, default="BiLSTM", help='SequenceModeling stage. None|BiLSTM')
parser.add_argument('--Prediction', type=str, default="Attn", help='Prediction stage. CTC|Attn')
parser.add_argument('--num_fiducial', type=int, default=20, help='number of fiducial points of TPS-STN')
parser.add_argument('--input_channel', type=int, default=1, help='the number of input channel of Feature extractor')
parser.add_argument('--output_channel', type=int, default=512,
                    help='the number of output channel of Feature extractor')
parser.add_argument('--hidden_size', type=int, default=256, help='the size of the LSTM hidden state')

opt = parser.parse_args()

if opt.sensitive:
	opt.character = string.printable[:-6]  # same with ASTER setting (use 94 char).

cudnn.benchmark = True
cudnn.deterministic = True
opt.num_gpu = torch.cuda.device_count()
""" model configuration """
if 'CTC' in opt.Prediction:
	converter = CTCLabelConverter(opt.character)
else:
	converter = AttnLabelConverter(opt.character)
opt.num_class = len(converter.character)
device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

cudnn.deterministic = True
model_textrec = recModel(opt)
model_textrec = torch.nn.DataParallel(model_textrec).to(device)
# load model
model_textrec.load_state_dict(torch.load("TPS-ResNet-BiLSTM-Attn.pth", map_location=device))
model_textrec.eval()
kf2 = keywordFinder_v2.Keywordfinder()
x=15000
sys.setrecursionlimit(x)
app = Flask(__name__)

def getRotate(imgPath):
	im = cv2.imread(imgPath)
	im = cv2.cvtColor(im,cv2.COLOR_BGR2GRAY)
	#im = ndimage.zoom(im,0.3)

	gray = im
	edges = cv2.Canny(gray,50,150,apertureSize = 3)
	lines = cv2.HoughLinesP(edges,1,np.pi/180,100,minLineLength=100,maxLineGap=10)
	max_dist = 0
	max_coord1 = []
	max_coord2 = []
	rAngle = 0


	for [line] in lines:
		a = np.array([line[0],line[1]])
		b = np.array([line[2],line[3]])
		dist = np.linalg.norm(a-b)
		if(dist > max_dist):
			max_dist = dist
			max_coord1 = a
			max_coord2 = b

	rise = (max_coord1[1]-max_coord2[1])
	run = max_coord1[0]-max_coord2[0]

	if np.isclose(run,0) or np.isclose(rise,0):
		rAngle = 0
	else:
		rAngle = math.atan2(rise, run)
	#print(max_coord1,max_coord2,rAngle)
	im_mod = ndimage.rotate(im, rAngle,cval = 255)
	newdata=pytesseract.image_to_osd(im_mod)
	rAngle2 = 360-int(re.search('(?<=Rotate: )\d+', newdata).group(0))
	return rAngle+rAngle2
def testBlack(red,green,blue):
	if red < 210 and green < 210 and blue < 210 and abs(int(red)-int(green)) < 100 and abs(int(red)-int(blue)) < 100 and abs(int(green)-int(blue)) < 100:
		return True
	else:
		return False
def rotateImage(image, angle):
	image_center = tuple(np.array(image.shape[1::-1]) / 2)
	rot_mat = cv2.getRotationMatrix2D(image_center, angle, 1.0)
	result = cv2.warpAffine(image, rot_mat, image.shape[1::-1], flags=cv2.INTER_LINEAR,borderValue=(255,255,255))
	return result
def rotateImageBlackBorder(image, angle):
	image_center = tuple(np.array(image.shape[1::-1]) / 2)
	rot_mat = cv2.getRotationMatrix2D(image_center, angle, 1.0)
	result = cv2.warpAffine(image, rot_mat, image.shape[1::-1], flags=cv2.INTER_LINEAR,borderValue=(0,0,0))
	return result
def init_feature(name):
    chunks = name.split('-')
    if chunks[0] == 'sift':
        detector = cv2.xfeatures2d.SIFT_create()
        norm = cv2.NORM_L2
    elif chunks[0] == 'surf':
        detector = cv2.xfeatures2d.SURF_create(800)
        norm = cv2.NORM_L2
    elif chunks[0] == 'orb':
        detector = cv2.ORB_create(400)
        norm = cv2.NORM_HAMMING
    elif chunks[0] == 'akaze':
        detector = cv2.AKAZE_create()
        norm = cv2.NORM_HAMMING
    elif chunks[0] == 'brisk':
        detector = cv2.BRISK_create()
        norm = cv2.NORM_HAMMING
    else:
        return None, None
    if 'flann' in chunks:
        if norm == cv2.NORM_L2:
            flann_params = dict(algorithm=FLANN_INDEX_KDTREE, trees=5)
        else:
            flann_params = dict(algorithm=FLANN_INDEX_LSH,
                                table_number=6,  # 12
                                key_size=12,     # 20
                                multi_probe_level=1)  # 2
        # bug : need to pass empty dict (#1329)
        matcher = cv2.FlannBasedMatcher(flann_params, {})
    else:
        matcher = cv2.BFMatcher(norm)
    return detector, matcher


def filter_matches(kp1, kp2, matches, ratio=0.75):
    mkp1, mkp2 = [], []
    for m in matches:
        if len(m) == 2 and m[0].distance < m[1].distance * ratio:
            m = m[0]
            mkp1.append(kp1[m.queryIdx])
            mkp2.append(kp2[m.trainIdx])
    p1 = np.float32([kp.pt for kp in mkp1])
    p2 = np.float32([kp.pt for kp in mkp2])
    kp_pairs = zip(mkp1, mkp2)
    return p1, p2, list(kp_pairs)

def moveVer(imageR,imageG,imageB,start,dir,times):
	x = start[0]
	y = start[1]
	while times > 0:
		y = y+dir*15
		while True:
			#print("now at",[x,y])
			isline = True
			for i in range(15):
				if not (testBlack(imageR[y][x+i],imageG[y][x+i],imageB[y][x+i]) or testBlack(imageR[y][x-i],imageG[y][x-i],imageB[y][x-i])):
					#print("not black",[x+i,y],"or",[x-i,y])
					#print([x+i,y],testBlack(imageR[y][x+i],imageG[y][x+i],imageB[y][x+i]))
					#print([x-i,y],testBlack(imageR[y][x-i],imageG[y][x-i],imageB[y][x-i]))
					isline = False
					break
			if isline:
				break
			"""
			if (testBlack(imageR[y][x+5],imageG[y][x+5],imageB[y][x+5]) and testBlack(imageR[y][x+4],imageG[y][x+4],imageB[y][x+4]) and testBlack(imageR[y][x+3],imageG[y][x+3],imageB[y][x+3]) and testBlack(imageR[y][x+2],imageG[y][x+2],imageB[y][x+2]) and testBlack(imageR[y][x+1],imageG[y][x+1],imageB[y][x+1])) or (testBlack(imageR[y][x-5],imageG[y][x-5],imageB[y][x-5]) and testBlack(imageR[y][x-4],imageG[y][x-4],imageB[y][x-4]) and testBlack(imageR[y][x-3],imageG[y][x-3],imageB[y][x-3]) and testBlack(imageR[y][x-2],imageG[y][x-2],imageB[y][x-2]) and testBlack(imageR[y][x-1],imageG[y][x-1],imageB[y][x-1])) :
				break
			"""
			y = y + dir
			if not testBlack(imageR[y][x],imageG[y][x],imageB[y][x]):
				[x,y] = findLine(imageR,imageG,imageB,[x,y],1,0)
		#print("RUN VER",[x,y])
		times -= 1

	return [x,y]
def moveHor(imageR,imageG,imageB,start,dir,times):
	x = start[0]
	y = start[1]

	while times > 0:
		x = x+dir*15
		while True:
			#print("now at",[x,y])
			isline = True
			for i in range(15):
				if not (testBlack(imageR[y+i][x],imageG[y+i][x],imageB[y+i][x]) or testBlack(imageR[y-i][x],imageG[y-i][x],imageB[y-i][x])):

					isline = False
					break
			if isline:
				break
			"""
			if (testBlack(imageR[y+5][x],imageG[y+5][x],imageB[y+5][x]) and testBlack(imageR[y+4][x],imageG[y+4][x],imageB[y+4][x]) and testBlack(imageR[y+3][x],imageG[y+3][x],imageB[y+3][x]) and testBlack(imageR[y+2][x],imageG[y+2][x],imageB[y+2][x]) and testBlack(imageR[y+1][x],imageG[y+1][x],imageB[y+1][x])) or (testBlack(imageR[y-5][x],imageG[y-5][x],imageB[y-5][x]) and testBlack(imageR[y-4][x],imageG[y-4][x],imageB[y-4][x]) and testBlack(imageR[y-3][x],imageG[y-3][x],imageB[y-3][x]) and testBlack(imageR[y-2][x],imageG[y-2][x],imageB[y-2][x]) and testBlack(imageR[y-1][x],imageG[y-1][x],imageB[y-1][x])) :
				break
			"""
			x = x + dir
			if not testBlack(imageR[y][x],imageG[y][x],imageB[y][x]):
				#print("line lost at",[x,y])
				[x,y] = findLine(imageR,imageG,imageB,[x,y],0,1)
		#print("RUN HOR",[x,y])
		times -= 1
	return [x,y]
def findLine(imageR,imageG,imageB,curr,dirX,dirY):
	x = curr[0]
	y = curr[1]
	count = 1
	#print("looking for line at",curr)
	while True:
		if testBlack(imageR[y+dirY*count][x+dirX*count],imageG[y+dirY*count][x+dirX*count],imageB[y+dirY*count][x+dirX*count]):
			x = x+dirX*count
			y = y+dirY*count
			break
		elif testBlack(imageR[y-dirY*count][x-dirX*count],imageG[y-dirY*count][x-dirX*count],imageB[y-dirY*count][x-dirX*count]):
			x = x-dirX*count
			y = y-dirY*count
			break
		count += 1
	return [x,y]
def getDensity(image,TL,BR):
	d_x = -(TL[0] - BR[0])
	d_y = -(TL[1] - BR[1])
	count = 0
	for i in range (d_x):
		for j in range(d_y):
			if testBlack(image[j,i,2],image[j,i,1],image[j,i,0]):
				count += 1
	return count/(d_x*d_y)
def getConnectedShape(image,canvas,x,y,threshold):
	d_x = image.shape[1]
	d_y = image.shape[0]
	#print("start at",x,y)
	#print("Count",count)
	for i in range(-threshold,threshold+1,1):
		for j in range(-threshold,threshold+1,1):
			if(x+i > 0 and y+j > 0 and x+i < d_x and y+j < d_y and (i !=0 or j !=0)):
				if canvas[y+j][x+i] == 255:
					if image[y+j][x+i][0] < 210:
						canvas[y+j][x+i] = 0
						image[y+j][x+i][:] = 255
						#print(i,j)
						getConnectedShape(image,canvas,x+i,y+j,threshold)
	return
def getConnectedShape_Opti(image,canvas,x,y,threshold,call_i,call_j):
	#print("OPTI "+str(image.shape),file=sys.stderr)
	d_x = image.shape[1]
	d_y = image.shape[0]
	if(call_i == 0 and call_j == 0):
		for i in range(-threshold,threshold+1,1):
			for j in range(-threshold,threshold+1,1):
				if(x+i > 0 and y+j > 0 and x+i < d_x and y+j < d_y and (i !=0 or j !=0)):
					if canvas[y+j][x+i] == 255:
						if image[y+j][x+i][0] < 210:
							canvas[y+j][x+i] = 120
							image[y+j][x+i][:] = 255
							#print(i,j)
							getConnectedShape_Opti(image,canvas,x+i,y+j,threshold,i,j)
	
	else:
		if(call_i < 0):
			for i in range(x-threshold,x-threshold-call_i):
				if(call_j < 0):
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
				else:
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
			if(call_j < 0):
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y-threshold,y-call_j-threshold):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)

			else:
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y+threshold+1-call_j,y+threshold+1):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
		else :
			for i in range(x+threshold-call_i+1,x+threshold+1):
				if(call_j < 0):
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
				else:
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
			if(call_j < 0):
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y-threshold,y-call_j-threshold):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
			else:
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y+threshold+1-call_j,y+threshold+1):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)


	return
def naiveInter(image,scale):
	d_x = image.shape[1]
	d_y = image.shape[0]
	#print("scale:",scale,"x:",d_x,"y:",d_y)
	output  = np.full([math.floor(d_y*scale+4),math.floor(d_x*scale+4)],255)
	scalar_flag = np.isscalar(image[0,0])
	for i in range(d_x):
		for j in range(d_y):
			if(scalar_flag):
				if(image[j,i] == 120):
					#if(j*scale+scale*4 < d_y*scale and i*scale+scale*4 < d_x):
					output[j*scale:j*scale+scale*4,i*scale:i*scale+scale*4] = image[j,i]
			else:
				if(any(image[j,i] == 120)):
					output[j*scale:j*scale+scale*4,i*scale:i*scale+scale*4,:] = image[j,i,:]
	return output
def naiveReduction(image,scale):
	d_x = image.shape[1]
	d_y = image.shape[0]
	#cv2.imwrite('shapes/TMP/RREEEEE.png', image)
	output  = np.full([math.ceil(d_y/scale),math.ceil(d_x/scale),3],255)
	print("O_S:",output.shape)
	for i in range(0,d_x,scale):
		for j in range(0,d_y,scale):
			#print(image[j:j+scale][i:i+scale])
			s_o = 255
			#print(np.count_nonzero(image[j:j+scale,i:i+scale,:] != 255))
			if(np.count_nonzero(image[j:j+scale,i:i+scale,:] != 255) > 0):
				s_o = 0
			#print(s_o)
			#print(i/scale,j/scale)
			output[int(j/scale)][int(i/scale)] = s_o
	return output

def job():
  i = 0
  while(True):
    # print("Child thread:", i, file=sys.stderr)
    i+=1
    time.sleep(1)
def compare(parts,compFiles,pid,hess,nL,thres):
	filepath = "../uploads/Crop/"
	#print(parts)

	#print(parts)
	output = {"data":[]}
	serial = 0
	for part in parts:
		part = part.get('filename')
		for fn in compFiles:
			# tmp = Comp.compare(filepath+fn,filepath+part,hess,nL,thres)
			img1 = cv2.imread(filepath+fn, 0)
			img2 = cv2.imread(filepath+part, 0)
			feature_name = 'sift'
			detector, matcher = init_feature(feature_name)
			if img1 is None:
				continue
				# print('Failed to load fn1:', fn1)
				# sys.exit(1)

			if img2 is None:
				continue
				# print('Failed to load fn2:', fn2)
				# sys.exit(1)

			if detector is None:
				continue
				# print('unknown feature:', feature_name)
				# sys.exit(1)

			# print('using', feature_name)

			kp1, desc1 = detector.detectAndCompute(img1, None)
			kp2, desc2 = detector.detectAndCompute(img2, None)
			# print('img1 - %d features, img2 - %d features' % (len(kp1), len(kp2)))

			def match_and_draw(win):
				status=[]
				try:
					raw_matches = matcher.knnMatch(desc1, trainDescriptors = desc2, k = 2) #2
				except:
					return 0
				p1, p2, kp_pairs = filter_matches(kp1, kp2, raw_matches)
				if len(p1) >= 4:
					H, status = cv2.findHomography(p1, p2, cv2.RANSAC, 5.0)
					# print(status)
					return np.sum(status) * 100 / len(status)
				return 0

				# _vis = explore_match(win, img1, img2, kp_pairs, status, H)
			result = match_and_draw('find_obj')
			serial += 1
			output.get('data').append({"source":part,"process_id":pid,"filename":fn,"confidence":result,"finish":serial,"total":len(parts)*len(compFiles)})
			r = requests.post("http://172.25.25.33:8082/result",json = output)
			# print(r.content,file=sys.stderr)
			data = r.json()
			if data['status']=="stop":
				return
			output = {"data":[]}
	r = requests.patch("http://172.25.25.33:8082/process/stop",json = {"id":[pid]})
	"""
	output = {"data":[]}
	thres = (1.5/9)*(thres-1)
	serial = 0
	for fn in compFiles:
		for part in parts:
			part = part.get('filename')
			#print(filepath+fn,filepath+part)
			#print(Comp.compare(filepath+fn,filepath+part))
			tmp = Comp.compare(filepath+fn,filepath+part,hess,nL,thres)
			serial += 1
			output.get('data').append({"process_id":pid,"filename":fn,"confidence":tmp[1],"finish":serial,"total":len(parts)*len(compFiles)})
			# print(output,file=sys.stderr)
			r = requests.post("http://172.25.25.33:8082/result",json = output)
			print(r.content,file=sys.stderr)
			data = r.json()
			if data['status']=="stop":
				return
			output = {"data":[]}
	"""
		#tmp = Comp.compare(filepath+fn,filepath+part)
	"""
	for fn in compFiles:
		print(fn,file=sys.stderr)
		if isinstance(parts, list):
			for part in parts:
				#print(Comp.compare(filepath+fn,filepath+part),file=sys.stderr)
				
				tmp = Comp.compare(filepath+fn,filepath+part.get("filename"))
				if(tmp[0]):
					print(pid,fn,part,tmp[1])
				
				#print("A")
				#print(filepath+"Crop/"+part,filepath+"Crop/"+fn)
				#print(Comp.compare(filepath+part,filepath+fn))
		else:
			tmp = Comp.compare(filepath+fn,filepath+parts.get(filename))
			if(tmp[0]):
				print(pid,fn,parts,tmp[1])
			#print(Comp.compare(filepath+fn,filepath+parts),file=sys.stderr)
	#print(pid,file=sys.stderr)
	"""
	# print(output,file=sys.stderr)

	#print(type(output))
	#r = requests.post("http://172.25.25.33:8082/result",data = {"data":[{"process_id":1,"filename":"123","confidence":0}]})
	# r = requests.post("http://172.25.25.33:8082/result",json = output)
	# print(r.content,file=sys.stderr)
	return

def match(parts,compFiles,pid,hess,nL,thres):
	filepath = "../uploads/"
	#print(parts)
	output = {"data":[]}
	serial = 0
	for part in parts:
		part = part.get('filename')
		for fn in compFiles:
			# tmp = Comp.compare(filepath+fn,filepath+part,hess,nL,thres)
			img1 = cv2.imread(filepath+fn, 0)
			img2 = cv2.imread(filepath+part, 0)
			feature_name = 'sift'
			detector, matcher = init_feature(feature_name)
			if img1 is None:
				continue
				# print('Failed to load fn1:', fn1)
				# sys.exit(1)

			if img2 is None:
				continue
				# print('Failed to load fn2:', fn2)
				# sys.exit(1)

			if detector is None:
				continue
				# print('unknown feature:', feature_name)
				# sys.exit(1)

			# print('using', feature_name)

			kp1, desc1 = detector.detectAndCompute(img1, None)
			kp2, desc2 = detector.detectAndCompute(img2, None)
			# print('img1 - %d features, img2 - %d features' % (len(kp1), len(kp2)))

			def match_and_draw(win):
				status=[]
				raw_matches = matcher.knnMatch(desc1, trainDescriptors = desc2, k = 2) #2
				p1, p2, kp_pairs = filter_matches(kp1, kp2, raw_matches)
				if len(p1) >= 4:
					H, status = cv2.findHomography(p1, p2, cv2.RANSAC, 5.0)
					# print(status)
					return np.sum(status) * 100 / len(status)
				return 0

				# _vis = explore_match(win, img1, img2, kp_pairs, status, H)
			result = match_and_draw('find_obj')
			serial += 1
			output.get('data').append({"process_id":pid,"filename":fn,"confidence":result,"finish":serial,"total":len(parts)*len(compFiles)})
			# print(output,file=sys.stderr)
			r = requests.post("http://172.25.25.33:8082/resultMatch",json = output)
			# print(r.content,file=sys.stderr)
			data = r.json()
			if data['status']=="stop":
				return
			output = {"data":[]}
		#tmp = Comp.compare(filepath+fn,filepath+part)
	r = requests.patch("http://172.25.25.33:8082/process/stop",json = {"id":[pid]})
	"""
	for fn in compFiles:
		print(fn,file=sys.stderr)
		if isinstance(parts, list):
			for part in parts:
				#print(Comp.compare(filepath+fn,filepath+part),file=sys.stderr)
				
				tmp = Comp.compare(filepath+fn,filepath+part.get("filename"))
				if(tmp[0]):
					print(pid,fn,part,tmp[1])
				
				#print("A")
				#print(filepath+"Crop/"+part,filepath+"Crop/"+fn)
				#print(Comp.compare(filepath+part,filepath+fn))
		else:
			tmp = Comp.compare(filepath+fn,filepath+parts.get(filename))
			if(tmp[0]):
				print(pid,fn,parts,tmp[1])
			#print(Comp.compare(filepath+fn,filepath+parts),file=sys.stderr)
	#print(pid,file=sys.stderr)
	"""
	# print(output,file=sys.stderr)

	#print(type(output))
	#r = requests.post("http://172.25.25.33:8082/result",data = {"data":[{"process_id":1,"filename":"123","confidence":0}]})
	return
def removeNoise(im):
	im = morphology_sci.grey_dilation(im, (3, 3)) - im
	global rotation_process
	rotation_process = np.array(im.shape)
	# Binarize.
	mean, std = im.mean(), im.std()
	t = mean + std
	im[im < t] = 0
	im[im >= t] = 1

	# Connected components.
	s = [[1,1,1],
	 [1,1,1],
	 [1,1,1]]
	lbl, numcc = label(im,structure=s)
	#lbls = np.arange(1, numcc + 1)
	unique, counts = np.unique(lbl, return_counts=True)
	failed = []
	for i in range(1,numcc+1):
		if counts[i] < 200:
			failed.append(i)
	for i in failed:
		lbl[lbl == i] = 0
	return np.array(lbl,dtype=bool)
def RecogNet(Boxes,ipath,rAngle,mode):
    lookup = [
			"1.23479",
			"1.2365",
			"1.2767",
			"4140",
			"sus420j2",
			"4340",
			"8620",
			"sus303",
			"asp23",
			"asp30",
			"asp60",
			"c1100 copper",
			"c3604 brass",
			"c90700 bronze",
			"c93210 bronze",
			"carbide",
			"cpm-10v",
			"cpm-3v",
			"cpm-m4",
			"d2",
			"g10",
			"g15",
			"g20",
			"g30",
			"g40",
			"g50",
			"g55",
			"hap10",
			"k340",
			"k890",
			"mil-60s",
			"m7",
			"mil-tip",
			"s390",
			"s45c",
			"s7",
			"sae64(c93700 bronze)",
			"sae660 bronze(c93200 bronze)",
			"sae841 bronze",
			"scm415",
			"scm435",
			"scm440",
			"d2",
			"h13",
			"mil-60",
			"solide carbride",
			"stelite",
			"suj2",
			"sus304",
			"t15",
			"v4",
			"w360",
			"mil-60r",
			"sus316",
			"sus420j1",
			"jiscac406c bronze",
			"c83600 bronze",
			"c95500 bronze",
			"red copper",
			"c17200 beryllium copper",
			"oxygen-free copper",
			"brass",
			"cucrzr copper",
			"aluminum",
			"c95400 bronze",
			"c95800 bronze",
			"c95810 bronze",
			"aluminum bar",
			"jis-c-5191 bronze",
			"cac502c bronze",
			"cusn6 bronze(c51900 bronze)"
			]

    parser = argparse.ArgumentParser()
    parser.add_argument('--image_folder', default="CutTexts", help='path to image_folder which contains text images')
    parser.add_argument('--workers', type=int, help='number of data loading workers', default=1)
    parser.add_argument('--batch_size', type=int, default=192, help='input batch size')
    parser.add_argument('--saved_model', default="TPS-ResNet-BiLSTM-Attn.pth", help="path to saved_model to evaluation")
    """ Data processing """
    parser.add_argument('--batch_max_length', type=int, default=25, help='maximum-label-length')
    parser.add_argument('--imgH', type=int, default=32, help='the height of the input image')
    parser.add_argument('--imgW', type=int, default=100, help='the width of the input image')
    parser.add_argument('--rgb', action='store_true', help='use rgb input')
    parser.add_argument('--character', type=str, default='0123456789abcdefghijklmnopqrstuvwxyz', help='character label')
    parser.add_argument('--sensitive', action='store_true', help='for sensitive character mode')
    parser.add_argument('--PAD', action='store_true', help='whether to keep ratio then pad for image resize')
    """ Model Architecture """
    parser.add_argument('--Transformation', type=str, default="TPS", help='Transformation stage. None|TPS')
    parser.add_argument('--FeatureExtraction', type=str, default="ResNet", help='FeatureExtraction stage. VGG|RCNN|ResNet')
    parser.add_argument('--SequenceModeling', type=str, default="BiLSTM", help='SequenceModeling stage. None|BiLSTM')
    parser.add_argument('--Prediction', type=str, default="Attn", help='Prediction stage. CTC|Attn')
    parser.add_argument('--num_fiducial', type=int, default=20, help='number of fiducial points of TPS-STN')
    parser.add_argument('--input_channel', type=int, default=1, help='the number of input channel of Feature extractor')
    parser.add_argument('--output_channel', type=int, default=512,
                        help='the number of output channel of Feature extractor')
    parser.add_argument('--hidden_size', type=int, default=256, help='the size of the LSTM hidden state')

    opt = parser.parse_args()
    print("RC0")
    """ vocab / character number configuration """
    if opt.sensitive:
        opt.character = string.printable[:-6]  # same with ASTER setting (use 94 char).

    cudnn.benchmark = True
    cudnn.deterministic = True
    opt.num_gpu = torch.cuda.device_count()
    """ model configuration """
    if 'CTC' in opt.Prediction:
        converter = CTCLabelConverter(opt.character)
    else:
        converter = AttnLabelConverter(opt.character)
    opt.num_class = len(converter.character)
    device = torch.device('cuda:0' if torch.cuda.is_available() else 'cpu')

    if opt.rgb:
        opt.input_channel = 3

    # prepare data. two demo images from https://github.com/bgshih/crnn#run-demo
    print("RC1")
    AlignCollate_demo = AlignCollate(imgH=opt.imgH, imgW=opt.imgW, keep_ratio_with_pad=opt.PAD)
    image = cv2.imread(ipath)
    Height,Width,chn = image.shape
    image = ndimage.rotate(image, rAngle,cval = 255)
    image = Image.fromarray(image)
    demo_data = RawDatasetWithBB(image=image, opt=opt,BB = Boxes)  # use RawDataset
    demo_loader = torch.utils.data.DataLoader(
        demo_data, batch_size=opt.batch_size,
        shuffle=False,
        num_workers=int(opt.workers),
        collate_fn=AlignCollate_demo, pin_memory=True)

    # predict
    model_textrec.eval()
    with torch.no_grad():
        print("RC2")
        minfuzz = 80
        wordlist = []
        count = 0
        matAt = []
        for image_tensors, BoundingBox in demo_loader:
            batch_size = image_tensors.size(0)
            print("RC2.1")
            image = image_tensors.to(device)
            # For max length prediction
            length_for_pred = torch.IntTensor([opt.batch_max_length] * batch_size).to(device)
            text_for_pred = torch.LongTensor(batch_size, opt.batch_max_length + 1).fill_(0).to(device)
            print("RC2.2")
            if 'CTC' in opt.Prediction:
                preds = model_textrec(image, text_for_pred)

                # Select max probabilty (greedy decoding) then decode index to character
                preds_size = torch.IntTensor([preds.size(1)] * batch_size)
                _, preds_index = preds.max(2)
                # preds_index = preds_index.view(-1)
                preds_str = converter.decode(preds_index, preds_size)

            else:
                preds = model_textrec(image, text_for_pred, is_train=False)

                # select max probabilty (greedy decoding) then decode index to character
                _, preds_index = preds.max(2)
                preds_str = converter.decode(preds_index, length_for_pred)

            print("RC2.5")
            preds_prob = torchF.softmax(preds, dim=2)
            preds_max_prob, _ = preds_prob.max(dim=2)
            print("RC3")
            for  BB,pred, pred_max_prob in zip(BoundingBox, preds_str, preds_max_prob):
                if 'Attn' in opt.Prediction:
                    pred_EOS = pred.find('[s]')
                    pred = pred[:pred_EOS]  # prune after "end of sentence" token ([s])
                    pred_max_prob = pred_max_prob[:pred_EOS]

                # calculate confidence score (= multiply of pred_max_prob)
                confidence_score = pred_max_prob.cumprod(dim=0)[-1]
                if(confidence_score > 0):
                    #fu = fuzz.ratio(pred,"material")
                    #fu2 = fuzz.ratio(pred,"werkstoff")
                    #fu = max(fu,fu2)
                    #wordlist.append([BB,pred,confidence_score,fu])
                    wordlist.append([BB,pred,confidence_score])
                    """
                    if(fu > minfuzz):
                        matAt.append(count)
                    count+=1
                    """
        if(mode == 'material'):
            materials,coatings,matlit,coatlit = keywordFinder.getPossibleMaterialsCoatings(wordlist,Height // 12,Width // 3,80)
            return materials,coatings,matlit,coatlit
        if(mode == 'note'):
            return noteFinder.getNoteBox_cluster(wordlist,Height,Width)
        """
        matLit = []
        possLit = []
        for at in matAt:
            wordlist[at].append(True)
            matLit.append(wordlist[at])
        #print(oim.shape)
        Height = Height // 12
        Width = Width // 6
        for mat in matLit:
            print(mat)
            TL = np.array(mat[0][0:2])
            for word in wordlist:
                if(len(word) > 4):
                    continue
                if(abs(word[0][0] - TL[0]) < Width and abs(word[0][1] - TL[1]) < Height):
                    possLit.append(word)
        for l in possLit:
            #print(l[1])
            mf = 0
            ma = -1
            for i in range(len(lookup)):
                cf = fuzz.token_sort_ratio(lookup[i],l[1])
                if(cf > mf):
                    mf = cf
                    ma = i
            l.append([lookup[ma],mf])
            print(l)
        return [matLit,possLit]
		"""
def getTextBox(net, image, refine_net=None):
    print("TB0")
    t0 = time.time()
    text_threshold = 0.6
    link_threshold = 0.4
    low_text = 0.4
    cuda = torch.cuda.is_available()
    poly = False

    # resize
    img_resized, target_ratio, size_heatmap = imgproc.resize_aspect_ratio(image, 1280, interpolation=cv2.INTER_LINEAR, mag_ratio=1.5)
    ratio_h = ratio_w = 1 / target_ratio

    # preprocessing
    x = imgproc.normalizeMeanVariance(img_resized)
    x = torch.from_numpy(x).permute(2, 0, 1)    # [h, w, c] to [c, h, w]
    x = Variable(x.unsqueeze(0))                # [c, h, w] to [b, c, h, w]
    if cuda:
        x = x.cuda()

    # forward pass
    with torch.no_grad():
        y, feature = net(x)

    # make score and link map
    score_text = y[0,:,:,0].cpu().data.numpy()
    score_link = y[0,:,:,1].cpu().data.numpy()
    print("TB1")
    # refine link
    if refine_net is not None:
        with torch.no_grad():
            y_refiner = refine_net(y, feature)
        score_link = y_refiner[0,:,:,0].cpu().data.numpy()

    t0 = time.time() - t0
    t1 = time.time()

    # Post-processing
    boxes, polys = craft_utils.getDetBoxes(score_text, score_link, text_threshold, link_threshold, low_text, poly)

    # coordinate adjustment
    boxes = craft_utils.adjustResultCoordinates(boxes, ratio_w, ratio_h)
    polys = craft_utils.adjustResultCoordinates(polys, ratio_w, ratio_h)
    for k in range(len(polys)):
        if polys[k] is None: polys[k] = boxes[k]

    t1 = time.time() - t1

    # render results (optional)
    render_img = score_text.copy()
    render_img = np.hstack((render_img, score_link))
    ret_score_text = imgproc.cvt2HeatmapImg(render_img)

    print("\ninfer/postproc time : {:.3f}/{:.3f}".format(t0, t1))
    
    polys2 = []
    for poly in polys:
        poly2 = [poly[0][0],poly[0][1],poly[2][0],poly[2][1]]
        polys2.append(poly2)
    polys2 = np.array(polys2)
    polys2 = merge.non_max_suppression_fast(polys2,0.8)
    polys2 = merge2.non_max_suppression_fast(polys2,0,5)
    #polys2 = merge2.non_max_suppression_fast(polys2,0,3)
    #polys2 = merge2.non_max_suppression_fast(polys2,0,5)
    print("TB2")
    polys3 = []
    for poly2 in polys2:
        poly3 = [[poly2[0],poly2[1]],[poly2[2],poly2[1]],[poly2[2],poly2[3]],[poly2[0],poly2[3]]]
        polys3.append(np.array(poly3))
    print("TB3")
    return boxes, polys3, ret_score_text

@app.route('/rotate')
def rotate():
	filename = request.args.get('filename')
	#Area = request.args.get('Area')
	filepath = "../uploads/"
	# print(filepath+filename, file=sys.stderr)
	im = cv2.imread(filepath+filename)##'111.jpg'
	im_tmp = np.array(im)
	x_tmp = im_tmp.shape[1]
	y_tmp = im_tmp.shape[0]
	im_tmp = Image.fromarray(im)
	im_tmp = ImageOps.grayscale(im_tmp)
	im_tmp = np.array(im_tmp)
	im_tmp = removeNoise(im_tmp)
	im_tmp = np.logical_not(im_tmp)
	im_tmp = 255*im_tmp
	#cv2.imwrite('C:/Users/Hilton/Desktop/Pre.jpg', 255*im_tmp)
	"""
	im2=cv2.cvtColor(im,cv2.COLOR_BGR2GRAY)
	im2=im2.astype(np.uint8)
	#cv2.imwrite('C:/Users/Hilton/Desktop/Pre.jpg', im2)
	nb_components, output, stats, centroids = cv2.connectedComponentsWithStats(im2, connectivity=8)
	sizes = stats[1:, -1]; nb_components = nb_components - 1
	im3 = np.zeros((output.shape))
	for i in range(0, nb_components):
	    if sizes[i] >= 0:
	        im3[output == i + 1] = 255  
	cv2.imwrite('C:/Users/Hilton/Desktop/Pre.jpg', im3)
	im3 = cv2.cvtColor(im3.astype('uint8'), cv2.COLOR_GRAY2RGB)
	im_N = np.array(im3)
	"""

	im_N = np.array(im_tmp)
	#print(im_N.shape)
	d_x = im_N.shape[1]
	d_y = im_N.shape[0]
	#im_r = im_N[:,:,0]
	#im_g = im_N[:,:,1]
	#im_b = im_N[:,:,2]
	# if(d_y > d_x):
	# 	im_r = np.rot90(im_r,3)
	# 	im_g = np.rot90(im_g,3)
	# 	im_b = np.rot90(im_b,3)
	# 	tmp = d_x
	# 	d_x = d_y
	# 	d_y = tmp

	#outputImg = rotateImage(outputImg,-0.6)
	#cv2.imwrite('color_img.jpg', outputImg)

	found = False
	for i in range(d_x):
		j = 0
		for k in range(i):
			if im_N[j+k][i-k] < 210: 
				print(im_N[j+k][i-k])
				topLeft = [i-k,j+k]
				found = True
				break
		if found:
			break
	#print("TL",topLeft)

	found = False
	for i in range(d_x):
		j = d_y - i - 1
		for k in range(i):
			#print(j+k,k)
			if im_N[j+k][k] < 210: 
				botLeft = [k,j+k]
				found = True
				break
		if found:
			break
	#print("BL First:",firstGuess,"Second",secondGuess)
	#print("BL",botLeft)

	rise = botLeft[1]-topLeft[1]
	run = botLeft[0]-topLeft[0]
	#print(rise,run)
	if run == 0:
		rAngle = 0
	else:
		rAngle = np.arctan(rise/run)*180/np.pi
		
	im = ndimage.rotate(im, rAngle,cval = 255)
	newdata=pytesseract.image_to_osd(im)
	im = ndimage.rotate(im, 360-int(re.search('(?<=Rotate: )\d+', newdata).group(0)),cval = 255)
	# if(rAngle > 0):
	# 	rAngle = rAngle-90
	# else:
	# 	rAngle = rAngle+90

	#im4 = ndimage.rotate(im, rAngle-90,cval = 255)
	#cv2.imwrite('C:/Users/Hilton/Desktop/Post.jpg', im4)

	#im = cv2.imread(filepath+filename)
	#im = ndimage.rotate(im, rAngle+(int(re.search('(?<=Rotate: )\d+', newdata).group(0))),cval = 255)
	#cv2.imwrite('C:/Users/Hilton/Desktop/Post_'+filename, im)
	
	#im_org = im
	#anything = []
	#angle = 0
	
	# for i in range(4):
	# 	im = ndimage.rotate(im_org, rAngle+90*i)


	# 	im = Image.fromarray(im)
	# 	custom_oem_psm_config = r'--psm 11'
	# 	OCRTest = im
	# 	Area_not_given = True
	# 	if(Area is not None):
	# 		Area = json.loads(Area)
	# 		TL = Area[0]
	# 		BR = Area[1]
	# 		OCRTest = OCRTest.crop((TL[0],TL[1],BR[0],BR[1]))
	# 		Area_not_given = False
	# 	data = pytesseract.image_to_data(OCRTest,config=custom_oem_psm_config)
	# 	data = data.split("\n")
	# 	data.pop(0)
	# 	everything = []
	# 	for dt in data:
	# 		dt = dt.split("\t")
	# 		if(len(dt) >= 11):
	# 			if(Area_not_given):
	# 				numbers = sum(c.isdigit() for c in dt[11])
	# 				if(numbers >= 6):
	# 					box = [int(i) for i in dt[6:10]]
	# 					everything.append({"bounding_box":box,"text":dt[11]})
	# 			else:
	# 				if(len(dt[11]) > 2):
	# 					box = [int(i) for i in dt[6:10]]
	# 					box2 = [TL[0],TL[1],0,0]
	# 					box = np.add(box,box2)
	# 					everything.append({"bounding_box":box.tolist(),"text":dt[11]})
	# 	if len(everything) > len(anything):
	# 		anything = everything
	# 		angle=i
	print(rAngle,(360-int(re.search('(?<=Rotate: )\d+', newdata).group(0))))
	return jsonify({"rotate":rAngle+(360-int(re.search('(?<=Rotate: )\d+', newdata).group(0)))})

@app.route('/cut')
def main0():
	#Main
	reduction = 4
	filename = request.args.get('filename')
	filepath = "../uploads/"
	# print(filepath+filename, file=sys.stderr)
	im = cv2.imread(filepath+filename)##'111.jpg'
	im_N = np.array(im)

	#print(im_N.shape, file=sys.stderr)
	d_x = im_N.shape[1]
	d_y = im_N.shape[0]
	im_r = im_N[:,:,0]
	im_g = im_N[:,:,1]
	im_b = im_N[:,:,2]
	if(d_y > d_x):
		im_r = np.rot90(im_r,3)
		im_g = np.rot90(im_g,3)
		im_b = np.rot90(im_b,3)
		tmp = d_x
		d_x = d_y
		d_y = tmp

	#outputImg = rotateImage(outputImg,-0.6)
	#cv2.imwrite('color_img.jpg', outputImg)

	found = False
	for i in range(d_x):
		j = 0
		for k in range(i):
			if im_r[j+k][i-k] < 210 and im_g[j+k][i-k] < 210 and im_b[j+k][i-k] < 210: 
				topLeft = [i-k,j+k]
				found = True
				break
		if found:
			break
	print("TL",topLeft)

	found = False
	for i in range(d_x):
		j = d_y-i
		for k in range(i):
			if im_r[j+k][d_x-1-k] < 210 and im_g[j+k][d_x-1-k] < 210 and im_b[j+k][d_x-1-k] < 210: 
				botRight = [d_x-1-k,j+k]
				found = True
				break
		if found:
			break
	print("BR",botRight)

	found = False
	for i in range(d_x):
		j = d_y - i - 1
		for k in range(i):
			#print(j+k,k)
			if im_r[j+k][k] < 210 and im_g[j+k][k] < 210 and im_b[j+k][k] < 210: 
				botLeft = [k,j+k]
				found = True
				break
		if found:
			break
	#print("BL First:",firstGuess,"Second",secondGuess)
	print("BL",botLeft)


	outputImg = np.zeros([d_y,d_x,3])
	outputImg[:,:,0] = im_r
	outputImg[:,:,1] = im_g
	outputImg[:,:,2] = im_b
	rise = botLeft[1]-topLeft[1]
	run = botLeft[0]-topLeft[0]
	rAngle = np.arctan(rise/run)*180/np.pi
	outputImg = rotateImage(outputImg,rAngle-90)
	cv2.imwrite(filepath+"rot.png", outputImg)
	new_b = outputImg[:,:,0]
	new_g = outputImg[:,:,1]
	new_r = outputImg[:,:,2]

	found = False
	for i in range(d_x):
		j = 0
		for k in range(i):
			if new_r[j+k][i-k] < 210 and new_g[j+k][i-k] < 210 and new_b[j+k][i-k] < 210: 
				topLeft = [i-k,j+k]
				found = True
				break
		if found:
			break
	print("Start at",topLeft)

	nextPos = moveHor(new_r,new_g,new_b,topLeft,1,1)
	print("First move",nextPos)
	nextPos = moveVer(new_r,new_g,new_b,nextPos,1,3)
	print("TL",nextPos)
	BPTopLeft = nextPos
	nextPos = moveHor(new_r,new_g,new_b,nextPos,1,4)
	print("Third move",nextPos)
	nextPos = moveVer(new_r,new_g,new_b,nextPos,1,1)
	print("BR",nextPos)
	BPBotRight = nextPos

	BP_d_x = BPBotRight[0]-BPTopLeft[0]
	BP_d_y = BPBotRight[1]-BPTopLeft[1]
	BPImg = np.zeros([BP_d_y,BP_d_x,3])
	Splits = np.zeros([BP_d_y,BP_d_x,3])
	BPImg[:,:,0] = new_b[BPTopLeft[1]:BPBotRight[1],BPTopLeft[0]:BPBotRight[0]]
	BPImg[:,:,1] = new_g[BPTopLeft[1]:BPBotRight[1],BPTopLeft[0]:BPBotRight[0]]
	BPImg[:,:,2] = new_r[BPTopLeft[1]:BPBotRight[1],BPTopLeft[0]:BPBotRight[0]]
	cv2.imwrite(filepath+'BP.png', BPImg)
	BPImg[0:5,:,:] = 255;
	BPImg[:,0:5,:] = 255;
	BPImg[BP_d_y-5:BP_d_y,:,:] = 255;
	BPImg[:,BP_d_x-5,BP_d_x:] = 255;
	"""
	for i in range(BP_d_x):
		for j in range(BP_d_y):
			if not testBlack(new_r[j+BPTopLeft[1],i+BPTopLeft[0]],new_g[j+BPTopLeft[1],i+BPTopLeft[0]],new_b[j+BPTopLeft[1],i+BPTopLeft[0]]):
				BPImg[j,i,:] = [255,255,255]
			else:
				BPImg[j,i,:] = [0,0,0]
	"""
	#cv2.imwrite(filepath+"BIN0_"+filename, BPImg)#noncolorful cutPic
	BPImg = cv2.cvtColor(BPImg.astype('uint8'), cv2.COLOR_BGR2GRAY)
	ret,BPImg = cv2.threshold(BPImg,170,255,cv2.THRESH_BINARY)
	BPImg = np.repeat(BPImg[:, :, np.newaxis], 3, axis=2)

	BPImg[0:5*reduction,:,:] = [255,255,255]
	BPImg[:,0:5*reduction,:] = [255,255,255]
	BPImg[BP_d_y-(5*reduction):BP_d_y,:,:] = [255,255,255]
	BPImg[:,BP_d_x-(5*reduction):BP_d_x,:] = [255,255,255]

	#cv2.imwrite(filepath+"BIN_"+filename, BPImg)#noncolorful cutPic
	#BPImg_reduced = cv2.resize(BPImg, (math.floor(BP_d_x/reduction), math.floor(BP_d_y/reduction)), interpolation=cv2.INTER_AREA)
	naive_red = naiveReduction(BPImg,reduction)
	BPImg_reduced = naive_red
	#cv2.imwrite(filepath+"Crop/TEST.png", BPImg_reduced)
	BPCanvas = np.full([math.floor(BP_d_y/reduction),math.floor(BP_d_x/reduction)],255)
	BPFullCanvas = np.full([BP_d_y,BP_d_x],255)
	#BPCanvas = np.full([BP_d_y,BP_d_x],255)

	nextPos = moveVer(new_r,new_g,new_b,nextPos,1,2)
	NOTopLeft = moveHor(new_r,new_g,new_b,nextPos,-1,1)
	print(NOTopLeft)
	NOBotRight = moveVer(new_r,new_g,new_b,nextPos,1,1)
	print(NOBotRight)

	NO_d_x = NOBotRight[0]-NOTopLeft[0]
	NO_d_y = NOBotRight[1]-NOTopLeft[1]
	NOImg = np.zeros([NO_d_y,NO_d_x,3])
	NOImg[:,:,0] = new_b[NOTopLeft[1]:NOBotRight[1],NOTopLeft[0]:NOBotRight[0]]
	NOImg[:,:,1] = new_g[NOTopLeft[1]:NOBotRight[1],NOTopLeft[0]:NOBotRight[0]]
	NOImg[:,:,2] = new_r[NOTopLeft[1]:NOBotRight[1],NOTopLeft[0]:NOBotRight[0]]
	serial="fixed"+filename
	cv2.imwrite(filepath+"Crop/"+serial, NOImg)

	#BPCanvas = np.full([BP_d_y,BP_d_x],255)
	parts_count = 1
	crop_names = []
	boxes = {}
	"""
	for i in range(BP_d_x):
		for j in range(BP_d_y):
			if BPImg[j,i,1] < 210:
				BPCanvas = np.full([BP_d_y,BP_d_x],255)
				getConnectedShape_Opti(BPImg,BPCanvas,i,j,1,0,0)
				count = np.count_nonzero(BPCanvas == 0)
				if count > 1000:
					nons = np.nonzero(BPCanvas == 0)
					TLY = min(nons[0])
					TLX = min(nons[1])
					BRY = max(nons[0])
					BRX = max(nons[1])
					BPCanvas = BPCanvas[TLY:BRY,TLX:BRX]
					cv2.imwrite(filepath+"Crop/"+str(parts_count)+"_"+filename, BPCanvas)
					crop_names.append(str(parts_count)+"_"+filename)
					parts_count+=1
	"""
	tst_c = 0
	for i in range(math.floor(BP_d_x/reduction)):
		for j in range(math.floor(BP_d_y/reduction)):
			if BPImg_reduced[j,i,0] < 210:
				BPCanvas = np.full([math.ceil(BP_d_y/reduction),math.ceil(BP_d_x/reduction)],255)
				getConnectedShape_Opti(BPImg_reduced,BPCanvas,i,j,2,0,0)
				count = np.count_nonzero(BPCanvas == 120)
				#cv2.imwrite(filepath+"Crop/"+str(tst_c)+"_TST_"+filename, BPCanvas)
				tst_c+=1
				if count > 250:
					BPCanvas = naiveInter(BPCanvas,reduction)		
					BPCanvasOut = np.full([BP_d_y,BP_d_x,3],255)
					TMP = np.logical_and(BPImg[:,:,0] == 0,BPCanvas[0:BP_d_y,0:BP_d_x] == 120)
					TMP = np.logical_not(TMP)
					for i in range(3):
						BPCanvasOut[:,:,i] = TMP
					BPCanvasOut = BPCanvasOut*255
					nons = np.nonzero(BPCanvasOut == 0)
					TLY = min(nons[0])
					TLX = min(nons[1])
					BRY = max(nons[0])
					BRX = max(nons[1])
					print([TLX,TLY],[BRX,BRY])
					BPCanvasOut = BPCanvasOut[TLY:BRY,TLX:BRX]
					cv2.imwrite(filepath+"Crop/"+str(parts_count)+"_"+filename, BPCanvasOut)
					crop_names.append(str(parts_count)+"_"+filename)
					boxes[str(parts_count)+"_"+filename] = [int(TLX),int(TLY),int(BRX)-int(TLX),int(BRY)-int(TLY)]
					parts_count+=1

	# print(crop_names,file=sys.stderr)
	return jsonify({"No_file":serial,"No_recog":NO_recog.getNO_img(NOImg),"Crop_file":crop_names,"Bounding_boxes":boxes})

# @app.route('/recog')
# @app.route('/test')
# def main1():
# 	filename = request.args.get('filename')
# 	filepath = str(pathlib.Path(__file__).parent.absolute())+"/../uploads/"
# 	return(NO_recog.getNO(filepath+filename))
@app.route('/textrecog')
def main2():
	filename = request.args.get('filename')
	return jsonify(TextRecog.Recog(filename))
@app.route('/match')
def main3():
	json_data = request.args.get('data')
	data = ast.literal_eval(json_data)
	thres_multi = request.args.get('threshold')
	if(thres_multi is None):
		thres_multi = 5
	else:
		thres_multi = int(thres_multi)
		if(thres_multi > 10):
			thres_multi = 10
		if(thres_multi < 1):
			thres_multi = 1
	nL = request.args.get('layers')
	if(nL is None):
		nL = 1
	else:
		nL = int(nL)
		if(nL < 1):
			nL = 1
		if(nL > 3):
			nL = 3
	hess = request.args.get('hessian')
	if(hess is None):
		hess = 400
	else:
		hess = int(hess)
		if(hess < 200):
			hess = 200
		if(hess > 800):
			hess = 800
	process_id = data["process_id"]
	components = data["components"]
	file_id = data["file_id"]
	filepath = "../uploads/"
	# _, _, compFiles = next(walk(filepath), (None, None, []))
	r = requests.get("http://172.25.25.33:8082/recognition/files/"+str(file_id))
	compFiles = r.json()['data']
	#print(type(compFiles[0]))
	# for parts in components:
		# print(parts.get('filename') in compFiles,file=sys.stderr)
		# if(parts.get('filename') in compFiles):
		# 	compFiles.remove(parts.get('filename'))
	# compFiles = [compFiles[0]]			
	# print(compFiles,file=sys.stderr)
	t = threading.Thread(target = match, args=(components,compFiles,process_id,hess,nL,thres_multi))
	t.start()
	"""
	for parts in components:
		#print(parts,file=sys.stderr)
		t = threading.Thread(target = compare, args=(parts,compFiles,process_id))
		t.start()
	"""
	#print(data["process_id"])
	#data = json_data['data']
	#process_id
	#components
	"""
	filename = request.args.get('filename')
	#filepath = "../uploads/"
	_, _, compFiles = next(walk(filepath+"Crop/"), (None, None, []))
	for fn in compFiles:
		tmp = Comp.compare(filepath+filename,filepath+"Crop/"+fn)
		if(tmp[0]):
			succ.append([tmp[1],fn])
	"""
	#print(filenames,file=sys.stderr)
	return json.dumps({'success':True}), 200, {'ContentType':'application/json'} 

@app.route('/compare')
def cmp():
	json_data = request.args.get('data')
	data = ast.literal_eval(json_data)
	thres_multi = request.args.get('threshold')
	if(thres_multi is None):
		thres_multi = 5
	else:
		thres_multi = int(thres_multi)
		if(thres_multi > 10):
			thres_multi = 10
		if(thres_multi < 1):
			thres_multi = 1
	nL = request.args.get('layers')
	if(nL is None):
		nL = 1
	else:
		nL = int(nL)
		if(nL < 1):
			nL = 1
		if(nL > 3):
			nL = 3
	hess = request.args.get('hessian')
	if(hess is None):
		hess = 400
	else:
		hess = int(hess)
		if(hess < 200):
			hess = 200
		if(hess > 800):
			hess = 800
	process_id = data["process_id"]
	component_id = data["component_id"]
	components = data["components"]
	filepath = "../uploads/"
	r = requests.get("http://172.25.25.33:8082/recognition/crops/"+str(component_id))
	compFiles = r.json()['data']
	# _, _, compFiles = next(walk(filepath+"Crop/"), (None, None, []))
	#print(type(compFiles[0]))
	# for parts in components:
	# 	for part in parts:
	# 		#print(type(part),file=sys.stderr)
	# 		if(part in compFiles):
	# 			compFiles.remove(part)
	#print(compFiles)
	t = threading.Thread(target = compare, args=(components,compFiles,process_id,hess,nL,thres_multi))
	t.start()
	"""
	for parts in components:
		#print(parts,file=sys.stderr)
		t = threading.Thread(target = compare, args=(parts,compFiles,process_id))
		t.start()
	"""
	#print(data["process_id"])
	#data = json_data['data']
	#process_id
	#components
	"""
	filename = request.args.get('filename')
	#filepath = "../uploads/"
	_, _, compFiles = next(walk(filepath+"Crop/"), (None, None, []))
	for fn in compFiles:
		tmp = Comp.compare(filepath+filename,filepath+"Crop/"+fn)
		if(tmp[0]):
			succ.append([tmp[1],fn])
	"""
	#print(filenames,file=sys.stderr)
	return ""

@app.route('/')
def index():
	t = threading.Thread(target = job)
	t.start()
	return '<h1>hello</h1>'

@app.route('/recog')
def main4():
	# print("LMAO", file=sys.stderr)
	# return jsonify({"No_file":Fname})
	Fname = request.args.get('filename')
	angle = float(request.args.get('rotate'))
	path = "../uploads/"
	custom_oem_psm_config = r'--psm 11 digits'

	im = cv2.imread(path+Fname)##'111.jpg'
	im_N = np.array(im)
	#print(im_N.shape)
	d_x = im_N.shape[1]
	d_y = im_N.shape[0]
	im_r = im_N[:,:,0]
	im_g = im_N[:,:,1]
	im_b = im_N[:,:,2]
	if(d_y > d_x):
		im_r = np.rot90(im_r,3)
		im_g = np.rot90(im_g,3)
		im_b = np.rot90(im_b,3)
		tmp = d_x
		d_x = d_y
		d_y = tmp

	#outputImg = rotateImage(outputImg,-0.6)
	#cv2.imwrite('color_img.jpg', outputImg)

	found = False
	for i in range(d_x):
		j = 0
		for k in range(i):
			if im_r[j+k][i-k] < 210 and im_g[j+k][i-k] < 210 and im_b[j+k][i-k] < 210: 
				topLeft = [i-k,j+k]
				found = True
				break
		if found:
			break
	print("TL",topLeft)

	found = False
	for i in range(d_x):
		j = d_y-i
		for k in range(i):
			if im_r[j+k][d_x-1-k] < 210 and im_g[j+k][d_x-1-k] < 210 and im_b[j+k][d_x-1-k] < 210: 
				botRight = [d_x-1-k,j+k]
				found = True
				break
		if found:
			break
	print("BR",botRight)

	found = False
	for i in range(d_x):
		j = d_y - i - 1
		for k in range(i):
			#print(j+k,k)
			if im_r[j+k][k] < 210 and im_g[j+k][k] < 210 and im_b[j+k][k] < 210: 
				botLeft = [k,j+k]
				found = True
				break
		if found:
			break
	#print("BL First:",firstGuess,"Second",secondGuess)
	print("BL",botLeft)


	outputImg = np.zeros([d_y,d_x,3])
	outputImg[:,:,0] = im_b
	outputImg[:,:,1] = im_g
	outputImg[:,:,2] = im_r
	rise = botLeft[1]-topLeft[1]
	run = botLeft[0]-topLeft[0]

	im = cv2.imread(path+Fname)

	
	im = ndimage.rotate(im, angle)

	cv2.imwrite(path+"recog1_"+Fname, im)
	im = Image.fromarray(im)


	data = pytesseract.image_to_data(im,config=custom_oem_psm_config)
	data = data.split("\n")
	data.pop(0)
	data.pop()
	everything = []
	box_list_new = []
	# return jsonify({"No_file":Fname})

	for st in data:
		li = st.split("\t")
		if(len(li[11]) != 0):
			box_list_new.append([li[6],li[7],li[8],li[9]])
			box = [int(i) for i in li[6:10]]
			everything.append({"bounding_box":box,"text":li[11]})
	im_N = np.array(im)
	im_Box = im_N.copy()

	for crds in box_list_new:
		cv2.rectangle(im_Box,(int(crds[0]),int(crds[1])),(int(crds[0])+int(crds[2]),int(crds[1])+int(crds[3])),(255,0,0),2)
		#cv2.rectangle(im_N,(int(crds[0]),int(crds[1])),(int(crds[0])+int(crds[2]),int(crds[1])+int(crds[3])),(255,255,255),-1)
	#cv2.imwrite('shapes/recog.png', im_N)
	# cv2.imwrite(path+"recog_"+Fname, im_Box)
	return jsonify(everything)
@app.route('/pdf')
def Pdf():
	Fname = request.args.get('filename')
	pages = convert_from_path('../uploads/'+Fname, 200)
	for page in pages:
		page.save('../uploads/'+os.path.splitext(Fname)[0]+'.jpg', 'JPEG')
	return jsonify({"filename":os.path.splitext(Fname)[0]+'.jpg'})
@app.route('/CustomerPlan')
def CPlan():
	Fname = request.args.get('fileName')
	Area = request.args.get('area')
	angle = (request.args.get('rotate'))
	keywords = request.args.get('keywords')
	path = "../uploads/"
	im_org = cv2.imread(path+Fname)
	# print(im_org)
	if(angle is not None):
		angle = float(angle)
		im = ndimage.rotate(im_org, angle)
	else:
		im = im_org
	#rAngle = getRotate(Fname)
	rAngle = angle 
	im = ndimage.rotate(im_org, rAngle,cval = 255)
	im = Image.fromarray(im)
	custom_oem_psm_config = r'--psm 11'
	OCRTest = im
	Area_not_given = True
	keywords_not_given = True
	if(Area is not None):
		Area = json.loads(Area)
		TL = Area[0]
		BR = Area[1]
		OCRTest = OCRTest.crop((TL[0],TL[1],BR[0],BR[1]))
		Area_not_given = False
	if(keywords is not None):
		keywords = json.loads(keywords)
		keywords_not_given = False
	data = pytesseract.image_to_data(OCRTest,config=custom_oem_psm_config)
	data = data.split("\n")
	data.pop(0)
	possibleIds = []
	keywords_ita = {'material':['materiale'],'hardness':['durezza'],'coating':['trattamenti termici','rivestimenti']}
	keywords_en = {'materiale':['material'],'durezza':['hardness'],'trattamenti termici':['coating'],'rivestimenti':['coating']}
	if(keywords_not_given):
		for dt in data:
			dt = dt.split("\t")
			if(len(dt) >= 11):
				numbers = sum(c.isdigit() for c in dt[11])
				if(Area_not_given):
					if(numbers >= 6):
						box = [int(i) for i in dt[6:10]]
						possibleIds.append({"bounding_box":box,"text":dt[11]})
				else:
					if(len(dt[11]) > 6 and numbers >= 3):
						box = [int(i) for i in dt[6:10]]
						box2 = [TL[0],TL[1],0,0]
						box = np.add(box,box2)
						possibleIds.append({"bounding_box":box.tolist(),"text":dt[11]})
		return jsonify(possibleIds)
	if(not keywords_not_given):
		allkeys = []
		allkeys.extend(keywords)
		for keys in keywords:
			if(keys in keywords_ita):
				allkeys.extend(keywords_ita[keys])
			if(keys in keywords_en):
				allkeys.extend(keywords_en[keys])
		possbleMatches = []
		for dt in data:
			dt = dt.split("\t")
			if(len(dt) >= 11):
				#matching = max(fuzz.ratio('materiale',dt[11]),fuzz.ratio('trattamenti termici',dt[11]),fuzz.ratio('durezza',dt[11]),fuzz.ratio('rivestimenti',dt[11]))
				matching = 0
				matched = ""
				o_text = ""
				for key in allkeys:
					#new_match = fuzz.ratio(key,dt[11])
					if(len(dt[11]) >= 4):
						new_match = fuzz.partial_ratio(key,dt[11].lower())
					else:
						new_match = 0
					if(new_match > matching):
						matching = new_match
						matched = key
						o_text = dt[11]
				# if(matching > 70):
				# 	print([matching,matched,o_text],file=sys.stderr)
				if(matching > 70):
					#print([matching,matched,o_text],file=sys.stderr)
					if(Area_not_given):
						box = [int(i) for i in dt[6:10]]
						box3 = box
					else:
						box = [int(i) for i in dt[6:10]]
						box2 = [TL[0],TL[1],0,0]
						box3 = np.add(box,box2)
						box3 = box3.tolist()


					if(matched in keywords_en):
						matched_en = keywords_en[matched]
					else:
						matched_en = matched
					possbleMatches.append([box,box3,o_text,matched,matched_en])
					#print(box,file=sys.stderr)
		#print(possbleMatches,file=sys.stderr)
		for matches in possbleMatches:
			box = matches[0]
			box_TL = box[0:2]
			#print(box,file=sys.stderr)
			for dt in data:
				dt = dt.split("\t")
				if(len(dt) >= 11):
					try:
						ocr_percent = int(dt[10])
						if(ocr_percent > 0 and (abs(int(dt[7])-box_TL[1]) < 20 and abs(box[0]+box[2]-int(dt[6])) < 20)):
							#matches.append(dt[6:12])
							#print(dt,file=sys.stderr)
							#dt_TL = dt[6:8]
							if(Area_not_given):
								valueBox = [int(i) for i in dt[6:10]]
							else:
								valueBox = [int(i) for i in dt[6:10]]
								valueBox2 = [TL[0],TL[1],0,0]
								valueBox = np.add(valueBox,valueBox2)
								valueBox = valueBox.tolist()
							value = dt[11]
							if('hardness'in matches[4][0]):
								print("Is HR not in value? ","HR" not in value)
							if("HR" not in value and 'hardness'in matches[4][0]):
								print("No hardness scale, looking:",value)
								for dt2 in data:
									dt2 = dt2.split("\t")
									if(len(dt2) >= 11):
										try:
											ocr_percent = int(dt2[10])
											if(ocr_percent > 0 and (abs(int(dt2[7])-int(dt[7])) < 20 and abs(int(dt[6])-int(dt2[6])) < 200)):
												if("HR" in dt2[11]):
													value = value + dt2[11]
													print("found scale:",dt2[11])
												else:
													print("found other:",dt2[11])
										except ValueError:
											continue
							matches.append([valueBox,value])

					except ValueError:
						continue
		matchesOutput = []
		matchedY = []
		Y_count = 0
		for matches in possbleMatches:
			Y_found = False
			val = ""
			valBox = []
			if(len(matches) > 5):
				val = matches[5][1]
				valBox = matches[5][0]
			for Ys in matchedY:
				if(abs(Ys[0] - matches[1][1]) < 20):
					matchesOutput[Ys[1]].append({"keyword":matches[2],"Bounding_box":matches[1],"value":val,"Value_box":valBox})
					Y_found = True
			if(not Y_found):
				matchesOutput.append([{"keyword":matches[2],"Bounding_box":matches[1],"value":val,"Value_box":valBox}])
				matchedY.append([matches[1][1],Y_count])
				Y_count+=1

			
		return jsonify(matchesOutput)
@app.route('/CustomerParts')
def CPart():
	Fname = request.args.get('fileName')
	Area = request.args.get('area')
	angle_str = request.args.get('rotate')
	if angle_str is None:
		angle = 0
	else:
		angle = float(request.args.get('rotate'))
	path = "../uploads/"
	print(path+Fname)
	im = cv2.imread(path+Fname)##'111.jpg'
	im_N = np.array(im)
	#print(im_N.shape)
	d_x = im_N.shape[1]
	d_y = im_N.shape[0]
	im_r = im_N[:,:,0]
	im_g = im_N[:,:,1]
	im_b = im_N[:,:,2]
	"""
	if(d_y > d_x):
		im_r = np.rot90(im_r,3)
		im_g = np.rot90(im_g,3)
		im_b = np.rot90(im_b,3)
		tmp = d_x
		d_x = d_y
		d_y = tmp
	"""
	#outputImg = rotateImage(outputImg,-0.6)
	#cv2.imwrite('color_img.jpg', outputImg)

	"""	
	found = False
	for i in range(d_x):
		j = 0
		for k in range(i):
			if im_r[j+k][i-k] < 210 and im_g[j+k][i-k] < 210 and im_b[j+k][i-k] < 210: 
				topLeft = [i-k,j+k]
				found = True
				break
		if found:
			break
	print("TL",topLeft)

	found = False
	for i in range(d_x):
		j = d_y-i
		for k in range(i):
			if im_r[j+k][d_x-1-k] < 210 and im_g[j+k][d_x-1-k] < 210 and im_b[j+k][d_x-1-k] < 210: 
				botRight = [d_x-1-k,j+k]
				found = True
				break
		if found:
			break
	print("BR",botRight)

	found = False
	for i in range(d_x):
		j = d_y - i - 1
		for k in range(i):
			#print(j+k,k)
			if im_r[j+k][k] < 210 and im_g[j+k][k] < 210 and im_b[j+k][k] < 210: 
				botLeft = [k,j+k]
				found = True
				break
		if found:
			break
	#print("BL First:",firstGuess,"Second",secondGuess)
	print("BL",botLeft)
	"""

	im = ndimage.rotate(im, angle)
	Area_not_given = False
	if(Area is None):
		
		#Area = []
		#Area.append([topLeft[0],topLeft[1]])
		#Area.append([botRight[0],botRight[1]])
		Area = [[0,0],[d_x,d_y]]
		Area_not_given = True
	#cv2.imwrite('C:/Users/Hilton/Desktop/scanTest/WTF.jpg', im)
	print(Area)
	if(Area_not_given):
		# print(jsonify(PartCut.getParts(Fname,Area,im)))
		return jsonify(PartCut.getParts(Fname,Area,im,angle))
	else:
		return jsonify(PartCut.getParts(Fname,json.loads(Area),im,angle))
@app.route('/CustomerCut')
def CCut():
	Fname = request.args.get('fileName')
	path = "../uploads/"
	filepath = "../uploads/"
	im = cv2.imread(path+Fname)
	im = np.rot90(im,3)
	BP_d_x = im.shape[1]
	BP_d_y = im.shape[0]
	parts_count = 1
	crop_names = []
	boxes = {}
	reduction = 5
	naive_red = naiveReduction(im,reduction)
	BPImg_reduced = naive_red
	im = cv2.cvtColor(im.astype('uint8'), cv2.COLOR_BGR2GRAY)
	ret,im = cv2.threshold(im,170,255,cv2.THRESH_BINARY)
	#cv2.imwrite("C:/Users/Hilton/Desktop/scanTest/TEST.png", BPImg_reduced)
	#BPCanvas = np.full([math.floor(BP_d_y/reduction),math.floor(BP_d_x/reduction)],255)
	for i in range(math.floor(BP_d_x/reduction)):
		for j in range(math.floor(BP_d_y/reduction)):
			if BPImg_reduced[j,i,0] < 210:
				BPCanvas = np.full([math.ceil(BP_d_y/reduction),math.ceil(BP_d_x/reduction)],255)
				getConnectedShape_Opti(BPImg_reduced,BPCanvas,i,j,1,0,0)
				count = np.count_nonzero(BPCanvas == 120)
				if count > 250:
					#cv2.imwrite(filepath+"Crop/"+str(parts_count)+"_1_"+Fname, BPCanvas)
					BPCanvas = naiveInter(BPCanvas,reduction)	
					#cv2.imwrite(filepath+"Crop/"+str(parts_count)+"_2_"+Fname, BPCanvas)	
					BPCanvasOut = np.full([BP_d_y,BP_d_x,3],255)
					TMP = np.logical_and(im[:,:] == 0,BPCanvas[0:BP_d_y,0:BP_d_x] == 120)
					TMP = np.logical_not(TMP)
					for i in range(3):
						BPCanvasOut[:,:,i] = TMP
					BPCanvasOut = BPCanvasOut*255
					#cv2.imwrite(filepath+"Crop/"+str(parts_count)+"_3_"+Fname, BPCanvasOut)
					nons = np.nonzero(BPCanvasOut == 0)
					TLY = min(nons[0])
					TLX = min(nons[1])
					BRY = max(nons[0])
					BRX = max(nons[1])
					print([TLX,TLY],[BRX,BRY])
					BPCanvasOut = BPCanvasOut[TLY:BRY,TLX:BRX]
					cv2.imwrite(filepath+"Crop/"+str(parts_count)+"_"+Fname, BPCanvasOut)
					crop_names.append(str(parts_count)+"_"+Fname)
					boxes[str(parts_count)+"_"+Fname] = [int(TLX),int(TLY),int(BRX)-int(TLX),int(BRY)-int(TLY)]
					parts_count+=1
	return jsonify({"No_file":"","No_recog":"","Crop_file":crop_names,"Bounding_boxes":boxes})
@app.route('/cutComponent')## rAngle rotate still not satisfactory, weird artifact if the image is rotated first
def CC():
	Fname = request.args.get('fileName')
	filepath = "../uploads/"
	return jsonify({"id":component.run(source=filepath+Fname)})	
@app.route('/matchCustomer')## rAngle rotate still not satisfactory, weird artifact if the image is rotated first
def MC():
	Fname = request.args.get('fileName')
	logos = request.args.get('logo')
	filepath = "../uploads/"
	print(filepath+Fname)
	return jsonify({"value":logo.run(source=filepath+Fname)})

	"""
	if(logos is None):
		return ""
	else:
		maxID = ""
		logos = json.loads(logos)
		maxMatch=0
		for logo in logos:
			logoName = logo['name']
			img1 = cv2.imread(filepath+Fname, 0)
			img2 = cv2.imread(filepath+logoName, 0)

			feature_name = 'sift'
			detector, matcher = init_feature(feature_name)
			if img1 is None:
				continue

			if img2 is None:

				continue

			if detector is None:

				continue


			kp1, desc1 = detector.detectAndCompute(img1, None)
			kp2, desc2 = detector.detectAndCompute(img2, None)

			def match_and_draw(win):
				status=[]
				raw_matches = matcher.knnMatch(desc1, trainDescriptors = desc2, k = 2) #2
				p1, p2, kp_pairs = filter_matches(kp1, kp2, raw_matches)
				if len(p1) >= 4:
					H, status = cv2.findHomography(p1, p2, cv2.RANSAC, 5.0)
					# print(status)
					return np.sum(status) * 100 / len(status)
				return 0

				# _vis = explore_match(win, img1, img2, kp_pairs, status, H)
			result = match_and_draw('find_obj')
			if result > maxMatch:
				maxMatch = result
				maxID = logo['file_id']
		print (maxID)
		return jsonify({"id":maxID})
	"""

@app.route('/cutLogo')## rAngle rotate still not satisfactory, weird artifact if the image is rotated first
def CL():
	Fname = request.args.get('fileName')
	box = request.args.get('box')
	threshold = request.args.get('threshold')
	Area = request.args.get('area')
	tmptype = request.args.get('type')
	angle = float(request.args.get('rotate'))
	if threshold is None:
		threshold = 1
	elif int(threshold) > 1:
		threshold = 1
	path = "../uploads/"
	im = cv2.imread(path+Fname)
	im = ndimage.rotate(im, angle)
	im = cv2.cvtColor(im.astype('uint8'), cv2.COLOR_BGR2GRAY)
	ret,im = cv2.threshold(im,170,255,cv2.THRESH_BINARY)
	im_N = np.array([im,im,im])
	im_N = np.moveaxis(im_N, [0,1,2],[2,0,1])
	im_N = im_N.astype(np.float32)

	if(box is None):
		return ""
	else:
		box = json.loads(box)
		boxCount = 0
		# outBoxes = {}
		# outNames = ''
		# return BBox
		boxCount += 1
		#-----#
		currName = tmptype+"_"+Fname
		# currName = "111111111111"
		TL = [int(box[0]),int(box[1])]
		BR = [(int(box[0])+int(box[2])),(int(box[1])+int(box[3]))]
		cv2.imwrite(path+currName, im_N[TL[1]:BR[1],TL[0]:BR[0],:].astype(np.uint8))
		# outNames.append(currName)
		# outBoxes[currName] = [box[0],box[1],box[2],box[3]]
	


	return jsonify({"name":currName})

@app.route('/PartsWithBox')## rAngle rotate still not satisfactory, weird artifact if the image is rotated first
def PWB():
	Fname = request.args.get('fileName')
	BBox = request.args.get('bounding_box')
	threshold = request.args.get('threshold')
	Area = request.args.get('area')
	angle_str = request.args.get('rotate')
	if angle_str is None:
		angle = 0
	else:
		angle = float(request.args.get('rotate'))
	if threshold is None:
		threshold = 1
	elif int(threshold) > 1:
		threshold = 1
	path = "../uploads/"
	im = cv2.imread(path+Fname)
	im = ndimage.rotate(im, angle)
	im = cv2.cvtColor(im.astype('uint8'), cv2.COLOR_BGR2GRAY)
	ret,im = cv2.threshold(im,170,255,cv2.THRESH_BINARY)
	im_N = np.array([im,im,im])
	im_N = np.moveaxis(im_N, [0,1,2],[2,0,1])
	im_N = im_N.astype(np.float32)
	#print(im_N.shape,file=sys.stderr)
	#cv2.imwrite('C:/Users/Hilton/Desktop/scanTest/REEE.png', im_N)
	if(BBox is None):
		return ""
	else:
		Boxes = json.loads(BBox)
		boxCount = 0
		outBoxes = {}
		outNames = []
		# return BBox
		for box in Boxes:
			boxCount += 1
			#-----#
			currName = str(boxCount)+"_"+Fname
			TL = [int(box[0]),int(box[1])]
			BR = [(int(box[0])+int(box[2])),(int(box[1])+int(box[3]))]
			cv2.imwrite(path+"Crop/"+currName, im_N[TL[1]:BR[1],TL[0]:BR[0],:].astype(np.uint8))
			outNames.append(currName)
			outBoxes[currName] = [box[0],box[1],box[2],box[3]]
			#-----#
			"""
			#print(box,file=sys.stderr)
			reduction = math.ceil(math.sqrt(int(box[2])*int(box[3])/100000))
			#print("reduction "+str(reduction),file=sys.stderr)
			#reduction = 1
			#TL = [math.floor(box[0]/reduction),math.floor(box[1]/reduction)]
			#BR = [math.floor((int(box[0])+int(box[2]))/reduction),math.floor((int(box[1])+int(box[3]))/reduction)]
			if(box[0] is None and box[1] is None and box[2] is None and box[3] is None):
				continue
			TL = [int(box[0]),int(box[1])]
			BR = [(int(box[0])+int(box[2])),(int(box[1])+int(box[3]))]
			smallBP = im_N[TL[1]:BR[1],TL[0]:BR[0],:]
			oriBP = smallBP
			ori_x = int(box[2])
			ori_y = int(box[3])
			#cv2.imwrite("C:/Users/Hilton/Desktop/scanTest/TESTs"+str(boxCount)+"_1.png",smallBP)
			#print("PPBIG "+str(smallBP.shape),file=sys.stderr)
			smallBP = naiveReduction(smallBP,reduction)
			#print("PPSMALL "+str(smallBP.shape),file=sys.stderr)
			#cv2.imwrite("C:/Users/Hilton/Desktop/scanTest/TESTs"+str(boxCount)+"_2.png",smallBP)
			#continue
			#TL = [math.floor(box[0]/reduction),math.floor(box[1]/reduction)]
			#BR = [math.floor((int(box[0])+int(box[2]))/reduction),math.floor((int(box[1])+int(box[3]))/reduction)]
			small_d_x = smallBP.shape[1]
			small_d_y = smallBP.shape[0]
			#small_d_x = box[2]
			#small_d_y = box[3]
			print(small_d_x,small_d_y,file=sys.stderr)
			subBoxCount = 0
			for i in range(small_d_x):
				for j in range(small_d_y):
					if smallBP[j,i].any() < 210:
						smallCanvas = np.full([small_d_y,small_d_x],255,dtype = np.uint8)
						getConnectedShape_Opti(smallBP,smallCanvas,i,j,math.ceil(threshold/reduction),0,0)
						count = np.count_nonzero(smallCanvas == 120)
						if count > 1500/reduction:
							subBoxCount += 1
							smallCanvas = naiveInter(smallCanvas,reduction)
							#overmarginX = smallCanvas.shape[1] - ori_x
							#overmarginY = smallCanvas.shape[0] - ori_y
							#cv2.imwrite("C:/Users/Hilton/Desktop/scanTest/TESTs"+str(boxCount)+"_"+str(subBoxCount)+"_3.png",smallCanvas)
							#cv2.imwrite("C:/Users/Hilton/Desktop/scanTest/TESTs"+str(boxCount)+"_"+str(subBoxCount)+"_4.png",smallCanvas[0:ori_y,0:ori_x])
							#print( [math.floor(overmarginY/2),ori_y+math.ceil(overmarginY/2),math.floor(overmarginX/2),ori_x+math.ceil(overmarginX/2)],file=sys.stderr)
							#TMP = np.logical_and(oriBP[:,:,0] == 0,smallCanvas[math.floor(overmarginY/2):ori_y+math.ceil(overmarginY/2),math.floor(overmarginX/2):ori_x+math.ceil(overmarginX/2)] == 120)
							TMP = np.logical_and(oriBP[:,:,0] == 0,smallCanvas[0:ori_y,0:ori_x] == 120)
							BPCanvasOut = np.full([ori_y,ori_x,3],255)
							for i in range(3):
								BPCanvasOut[:,:,i] = TMP
							BPCanvasOut = BPCanvasOut*255
							nons = np.nonzero(BPCanvasOut == 255)
							TLY = min(nons[0])
							TLX = min(nons[1])
							BRY = max(nons[0])
							BRX = max(nons[1])
							print([TLX,TLY],[BRX,BRY])
							BPCanvasOut = BPCanvasOut[TLY:BRY,TLX:BRX]
							BPCanvasOut = rotateImageBlackBorder(BPCanvasOut.astype(np.float32),angle)
							#BPCanvasOut = rotateImage(BPCanvasOut.astype(np.float32),rAngle-90)
							#nons = np.nonzero(smallCanvas == 120)
							#nons = np.nonzero(smallCanvas == 120)
							#TLY = min(nons[0])
							#TLX = min(nons[1])
							#BRY = max(nons[0])
							#BRX = max(nons[1])
							#print([TLX,TLY],[BRX,BRY])
							#smallCanvas = smallCanvas[TLY:BRY,TLX:BRX]
							#smallCanvas = cv2.bitwise_not(smallCanvas)
							#ret,smallCanvas = cv2.threshold(smallCanvas,115,255,cv2.THRESH_BINARY)
							currName = str(boxCount)+"_"+str(subBoxCount)+"_"+Fname
							cv2.imwrite(path+"Crop/"+currName, BPCanvasOut)
							outNames.append(currName)
							outBoxes[currName] = [int(TLX)*reduction+TL[0],int(TLY)*reduction+TL[1],(int(BRX)-int(TLX))*reduction,(int(BRY)-int(TLY))*reduction]

			#print(smallBP.shape[:],file=sys.stderr)
		#print(Boxes,file=sys.stderr)
		"""
		return jsonify({"Crop_file":outNames,"Bounding_boxes":outBoxes})
def QuotationPDF(files,self):

	files = json.loads(files)
	columns = ['order_name','comment','comment','material','titanizing','num','cost','cost2','remark']
	html_file = ''
	customer = ''
	enquiry = ''
	deadline = ''
	now = ''
	for num,file in enumerate(files):
		print(file)
		if 'customer' not in file:
			customer = ''
		else:
			customer = file['customer']
		if 'customer' not  in file:
			enquiry = ''
		else:
			enquiry = file['customer']
		if 'deadline' not  in file:
			deadline = ''
		else:
			deadline = file['deadline']
		if 'update_time' not  in file:
			now = ''
		else:
			now = file['update_time']

		html_file += '<tr>'
		html_file += f'<td>{(num+1)}</td>'
		for column in columns:
			if column in file:
				html_file += f'<td>{file[column]}</td>'
			else:
				html_file += f'<td></td>'
		html_file += f'<td>ASAP</td>'
		html_file += '</tr>'
	print(html_file)

	body = f"""
		<html>
		<head>
			<meta charset="UTF-8">
			<meta name="pdfkit-page-size" content="a4"/>
			<meta name="pdfkit-orientation" content="Landscape"/>
		</head>
		<table style="border: 2px solid black;">
			<tbody>
				<tr>
					<td>
						<img src="https://yt3.ggpht.com/ytc/AKedOLQBo9_Hde1eK2fkZRkLH_0mscpQgghg93Er7-b7=s48-c-k-c0x00ffffff-no-rj" style="height:100px;width:100px">
					</td>
					<td  style="font-family:Sans-serif;font-size:30px" width="50%">
						<span>Major Industries Ltd.</span></br>
						<span>1,Chang-Tai St.,Hsiao-Kang</span></br>
						<span>Kaohsiung, Taiwan, R.O.C.</span></br>
					</td>
					<td  style="font-family:Sans-serif;font-size:30px" width="50%">
						<span>Tel:  886-7-8716711</span></br>
						<span>Fax:  886-7-8715935</span></br>
						<span>eMail:  milmajor@mil.com.tw</span></br>
					</td>
				</tr>
			</tbody>
		</table>
		<p style="text-align:right">Date:2021/07/29</p>
		<table width="100%">
			<tbody>
				<tr>
					<td style="font-family:Sans-serif;font-size:30px" width="40%">
						<p>Customer:{customer}</p>
					</td>
					<td style="font-family:Sans-serif;font-size:30px;font-weight:bold;" width="60%">
						<p>enquiry:{enquiry}</p>
					</td>
				</tr>
			</tbody>
		</table>
		<table border=1 style="border-collapse: collapse;font-family:Sans-serif;width:100%;font-size:20px">
			<thead>
				<tr>
					<th rowspan=2>Pos.</th>
					<th rowspan=2>Ident-No.</th>
					<th rowspan=2>Date of</br>    Drawing</th>
					<th rowspan=2>Description</th>
					<th rowspan=2>Material</th>
					<th rowspan=2>PVD</th>
					<th rowspan=2>Qty</th>
					<th>&#128;/Pc.</th>
					<th>&#128;/Pc.</th>
					<th></th>
					<th rowspan=2>Delivery</br>date arriving</th>
				</tr>
				<tr>
					<th>CIF Beckingen</th>
					<th>UPDATE PRICE</th>
					<th>REMARK</th>
				</tr>
			</thead>
			<tbody style="text-align:center">
				{html_file}
			</tbody>
		</table>
		<table width="100%">
			<tbody>
				<tr>
					<td style="font-family:Sans-serif;font-size:30px" width="33%">
					</td>
					<td style="font-family:Sans-serif;font-size:30px;font-weight:bold;" width="33%">
					</td>
					<td width="33%">
						<p style="font-family:Sans-serif;font-size:30px">Delivery: {deadline}</p>
						<p/>
						<p/>
						<span style="font-family:Sans-serif;font-size:25px">Thank you very much.</span><br/>
						<span style="font-family:Sans-serif;font-size:25px">Best regrads,</span><br/>
						<span style="font-family:Sans-serif;font-size:25px">Cathy {now}</span><br/>
					</td>
				</tr>
			</tbody>
		</table>
		</html>
		"""

	# print(body,file=sys.stderr)

	ts = time.time()
	fileName='out.pdf'
	print(fileName)



	pdfkit.from_string(body, '../uploads/out.pdf') #with --page-size=Legal and --orientation=Landscape
	print(fileName)
	sys.exit(0)
	return fileName

@app.route('/quotation', methods=['GET', 'POST'])## rAngle rotate still not satisfactory, weird artifact if the image is rotated first
def Quotation():
	files = request.form.get('files')
	print(files)
	
	t = threading.Thread(target = QuotationPDF, args=(files,''))
	t.start()

	return jsonify({"name":"out.pdf"})
@app.route('/CNNPartFilter')
def CNNPartFilter():
	json_data = request.args.get('crops')
	if json_data is not None:
		data = ast.literal_eval(json_data)
	else:
		return jsonify("Input name error")
	if 'paths' in data:
		FPaths = data['paths']
	else:
		return jsonify("File name error")
	output = []
	for FPath in FPaths:
		print(FPath)

		im = cv2.imread(FPath)
		im = cv2.cvtColor(im, cv2.COLOR_BGR2GRAY)
		im_N = np.array(im)
		width, height = im_N.shape
		if(width > height):
			padded = cv2.copyMakeBorder(im_N, 0, width-height, 0, 0,cv2.BORDER_CONSTANT,value = 255)
		else:
			padded = cv2.copyMakeBorder(im_N, 0, 0, 0, height-width,cv2.BORDER_CONSTANT,value = 255)
		padded = cv2.resize(padded, (1000,1000), interpolation = cv2.INTER_AREA)
		padded = cv2.cvtColor(padded, cv2.COLOR_GRAY2BGR)
		padded_pil = Image.fromarray(padded)
		#cv2.imshow('A',part)
		#cv2.waitKey(0)
		a = preprocess(padded_pil)
		a = Variable(torch.unsqueeze(a, dim=0), requires_grad=False)
		#print(a)
		result = model(a)
		result = result.tolist()
		if(result[0][0] > result[0][1] or result[0][1] < 0):
			output.append({"path":FPath,"isPart":False})
		else:
			output.append({"path":FPath,"isPart":True})
		print(result)

	return jsonify(output)

@app.route('/recognition/<customer>/<checkpoint>')
def get_checkpoint(customer=None, checkpoint=None):
	checkpoint_list = os.listdir('test/checkpoint')
	annotation_list = os.listdir('test/annotation')
	annotation_dict = {}
	for anno in annotation_list:
		check_anno = '_'.join(anno.split('_')[0:2])
		if (customer+'_'+checkpoint) == check_anno:
			# print(anno)
			with open('test/annotation/' + anno, 'r', encoding='utf-8') as R:
				for line in R.readlines():
					line = line.split('\n')[0].split(',')
					index = line[1]
					anno_class = line[2]
					if index not in annotation_dict:
						annotation_dict[index] = anno_class
	# print(annotation_dict)
	checkpoint_exist = False
	for pth in checkpoint_list:
		check = customer + '_' + checkpoint
		if check in pth:
			checkpoint_path = 'test/checkpoint/' + pth
			checkpoint_class = int(pth.split('_')[2])
			checkpoint_exist = True
	if checkpoint_exist == True:
		print(checkpoint_exist)
		# os.environ['CUDA_LAUNCH_BLOCKING'] = "1"
		customer_model = models.vgg11_bn(pretrained=False)
		customer_model.classifier[6] = nn.Linear(in_features=4096, out_features=checkpoint_class)
		checkpoint = torch.load(checkpoint_path, map_location=torch.device(device))
		customer_model.load_state_dict(checkpoint)
		customer_model.eval()
	else:
		return 'Checkpoint is not exist'
	json_data = request.args.get('crops')
	if json_data is not None:
		data = ast.literal_eval(json_data)
	else:
		return jsonify("Input name error")
	if 'paths' in data:
		FPaths = data['paths']
	else:
		return jsonify("File name error")
	top_k = request.args.get('top_k')
	if(top_k is None):
		top_k = 5
	else:
		top_k = int(top_k)

	GoodPaths = []
	Boxes = []
	currIm = None
	for FPath in FPaths:
		print(FPath)

		im = cv2.imread(FPath)
		im = cv2.cvtColor(im, cv2.COLOR_BGR2GRAY)
		im_N = np.array(im)
		width, height = im_N.shape
		if(width > height):
			padded = cv2.copyMakeBorder(im_N, 0, width-height, 0, 0,cv2.BORDER_CONSTANT,value = 255)
		else:
			padded = cv2.copyMakeBorder(im_N, 0, 0, 0, height-width,cv2.BORDER_CONSTANT,value = 255)
		padded = cv2.resize(padded, (1000,1000), interpolation = cv2.INTER_AREA)
		padded = cv2.cvtColor(padded, cv2.COLOR_GRAY2BGR)
		padded_pil = Image.fromarray(padded)
		#cv2.imshow('A',part)
		#cv2.waitKey(0)
		a = preprocess(padded_pil)
		a = Variable(torch.unsqueeze(a, dim=0), requires_grad=False)
		#print(a)
		result = model(a)
		result = result.tolist()
		if(result[0][0] < result[0][1] and result[0][1] > 0):
			GoodPaths.append([FPath,im_N.shape])
			Boxes.append(im_N.shape)
	ndboxes = np.array(Boxes)
	if(ndboxes.shape[0] > 0):
		ymax = max(ndboxes[:,0])
	else:
		return jsonify([])
	currIm = None
	for FPath,Box in GoodPaths:
		im = cv2.imread(FPath)
		im = cv2.cvtColor(im, cv2.COLOR_BGR2GRAY)
		part = np.array(im)	
		if(Box[0] < ymax):
			part = cv2.copyMakeBorder(part,0,ymax-Box[0],0,0,cv2.BORDER_CONSTANT,value = 255)
		if(currIm is None):
			currIm = part
		else:
			currIm = cv2.hconcat([currIm,part])

	width, height = currIm.shape
	if(width > height):
		padded = cv2.copyMakeBorder(currIm, 0, width-height, 0, 0,cv2.BORDER_CONSTANT,value = 255)
	else:
		padded = cv2.copyMakeBorder(currIm, 0, 0, 0, height-width,cv2.BORDER_CONSTANT,value = 255)
	padded = cv2.resize(padded, (1000,1000), interpolation = cv2.INTER_AREA)
	padded = cv2.cvtColor(padded, cv2.COLOR_GRAY2BGR)
	padded_pil = Image.fromarray(padded)
	#cv2.imshow('A',part)
	#cv2.waitKey(0)
	a = preprocess(padded_pil)
	a = Variable(torch.unsqueeze(a, dim=0), requires_grad=False)
	#print(a)
	result = customer_model(a)
	prob,ind = torch.sort(result,descending=True)
	ind = ind.tolist()
	prob = prob.tolist()
	output = [ind[0][0:top_k],prob[0][0:top_k]]
	print(output)
	for i, n in enumerate(output[0]):
		output[0][i] = annotation_dict[str(n)]
	return jsonify(output)
@app.route('/CNNPartSuggestion')
def CNNPartSuggestion():
	json_data = request.args.get('crops')
	if json_data is not None:
		data = ast.literal_eval(json_data)
	else:
		return jsonify("Input name error")
	if 'paths' in data:
		FPaths = data['paths']
	else:
		return jsonify("File name error")
	top_k = request.args.get('top_k')
	if(top_k is None):
		top_k = 5
	else:
		top_k = int(top_k)

	GoodPaths = []
	Boxes = []
	currIm = None
	for FPath in FPaths:
		print(FPath)

		im = cv2.imread(FPath)
		im = cv2.cvtColor(im, cv2.COLOR_BGR2GRAY)
		im_N = np.array(im)
		width, height = im_N.shape
		if(width > height):
			padded = cv2.copyMakeBorder(im_N, 0, width-height, 0, 0,cv2.BORDER_CONSTANT,value = 255)
		else:
			padded = cv2.copyMakeBorder(im_N, 0, 0, 0, height-width,cv2.BORDER_CONSTANT,value = 255)
		padded = cv2.resize(padded, (1000,1000), interpolation = cv2.INTER_AREA)
		padded = cv2.cvtColor(padded, cv2.COLOR_GRAY2BGR)
		padded_pil = Image.fromarray(padded)
		#cv2.imshow('A',part)
		#cv2.waitKey(0)
		a = preprocess(padded_pil)
		a = Variable(torch.unsqueeze(a, dim=0), requires_grad=False)
		#print(a)
		result = model(a)
		result = result.tolist()
		if(result[0][0] < result[0][1] and result[0][1] > 0):
			GoodPaths.append([FPath,im_N.shape])
			Boxes.append(im_N.shape)
	ndboxes = np.array(Boxes)
	if(ndboxes.shape[0] > 0):
		ymax = max(ndboxes[:,0])
	else:
		return jsonify([])
	currIm = None
	for FPath,Box in GoodPaths:
		im = cv2.imread(FPath)
		im = cv2.cvtColor(im, cv2.COLOR_BGR2GRAY)
		part = np.array(im)	
		if(Box[0] < ymax):
			part = cv2.copyMakeBorder(part,0,ymax-Box[0],0,0,cv2.BORDER_CONSTANT,value = 255)
		if(currIm is None):
			currIm = part
		else:
			currIm = cv2.hconcat([currIm,part])

	width, height = currIm.shape
	if(width > height):
		padded = cv2.copyMakeBorder(currIm, 0, width-height, 0, 0,cv2.BORDER_CONSTANT,value = 255)
	else:
		padded = cv2.copyMakeBorder(currIm, 0, 0, 0, height-width,cv2.BORDER_CONSTANT,value = 255)
	padded = cv2.resize(padded, (1000,1000), interpolation = cv2.INTER_AREA)
	padded = cv2.cvtColor(padded, cv2.COLOR_GRAY2BGR)
	padded_pil = Image.fromarray(padded)
	#cv2.imshow('A',part)
	#cv2.waitKey(0)
	a = preprocess(padded_pil)
	a = Variable(torch.unsqueeze(a, dim=0), requires_grad=False)
	#print(a)
	result = model2(a)
	prob,ind = torch.sort(result,descending=True)
	ind = ind.tolist()
	prob = prob.tolist()
	output = [ind[0][0:top_k],prob[0][0:top_k]]
	print(output)

	return jsonify(output)
@app.route('/CNNTextRec')
def CNNTextRec():
	json_data = request.args.get('Files')
	if json_data is not None:
		data = ast.literal_eval(json_data)
	else:
		return jsonify("Input name error")
	poss_mats = {}
	poss_coats = {}
	out = {}
	for file in data:
		rAngle = 0
# 		rAngle = getRotate(file)
		# image = imgproc.loadImage(file)
# 		image = ndimage.rotate(image, rAngle,cval = 255)
		material,coating = kf2.get_material_coating(file)
		out[file] = {'coating':[coating],'material':[material]}
		# bboxes, polys, score_text = getTextBox(model_textbox, image)
		"""
		material,matlist = RecogNet(polys,file,rAngle)
		if(len(matlist) == 0):
		    poss_mats[file] = "None"
		    continue
		best = 0
		promat = ""
		for m in matlist:
		    if(m[4][1] > best):
		    	best = m[4][1]
		    	promat = m[4][0]
		poss_mats[file] = promat
		"""
		"""
		materials,coatings,matlit,coatlit = RecogNet(polys,file,rAngle,'material')
		out[file] = {'matlit':[],'material':[],'coatlit':[],'coating':[]}
		for mat in materials:
			out[file]['material'].append(mat[-1])
		for ml in matlit:
			out[file]['matlit'].append([[int(tmp) for tmp in ml[0]],ml[1]])
		for coat in coatings:
			out[file]['coating'].append(coat[-1])
		for cl in coatlit:
			out[file]['coatlit'].append([[int(tmp) for tmp in cl[0]],cl[1]])
		"""
		
	return jsonify(out)
	#print(material,matlist)
	#return jsonify(poss_mats)
@app.route('/NoteRec')
def NoteRec():
	json_data = request.args.get('Files')
	if json_data is not None:
		data = ast.literal_eval(json_data)
	else:
		return jsonify("Input name error")
	noteBoxes = {}
	for file in data:
		image = imgproc.loadImage(file)
		bboxes, polys, score_text = getTextBox(model_textbox, image)
		noteBoxes[file] = RecogNet(polys,file,0,'note')
	return jsonify(noteBoxes)

@app.route('/messageParse')
def MsgParse():
	json_data = request.args.get('Files')
	if(json_data is not None):
		data = ast.literal_eval(json_data)
	else:
		return jsonify('Input name error')
	return jsonify(messageParser.msgParse(data))

@app.route('/pdfSplit')
def PSplit():
	json_data = request.args.get('Files')
	if(json_data is not None):
		data = ast.literal_eval(json_data)
	else:
		return jsonify('Input name error')
	return jsonify(pdfSplit.split_jpg(data))
@app.route('/orderParse3')
def OParse():
	json_data = request.args.get('Files')
	if(json_data is not None):
		data = ast.literal_eval(json_data)
	else:
		return jsonify('Input name error')
	return jsonify(orderformParser.extract_cell_images_from_table(cv2.imread("../uploads/"+data[0], cv2.IMREAD_GRAYSCALE)))
@app.route('/orderParse2')
def OParse2():
	json_data = request.args.get('Files')
	if(json_data is not None):
		data = ast.literal_eval(json_data)
	else:
		return jsonify('Input name error')
	img = cv2.imread("../uploads/"+data[0],0)
	template = img
	template = cv2.cvtColor(template, cv2.COLOR_BGR2GRAY)
	template = cv2.Canny(template, 50, 200)
	(h, w) = template.shape[:2]

	for imagePath in glob.glob("img2" + "/*.jpg"):
		image = cv2.imread(imagePath)
		gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
		found = None

		for scale in np.linspace(0.2, 1.0, 20)[::-1]:
			resized = imutils.resize(gray, width=int(gray.shape[1] * scale))
			r = gray.shape[1] / float(resized.shape[1])

			if resized.shape[0] < h or resized.shape[1] < w:
				break

			edged = cv2.Canny(resized, 50, 200)
			result = cv2.matchTemplate(edged, template, cv2.TM_CCOEFF)
			(_, maxVal, _, maxLoc) = cv2.minMaxLoc(result)

			if found is None or maxVal > found[0]:
				found = (maxVal, maxLoc, r)

		(_, maxLoc, r) = found
		(startX, startY) = (int(maxLoc[0] * r), int(maxLoc[1] * r))
		(endX, endY) = (int((maxLoc[0] + w) * r), int((maxLoc[1] + h) * r))

		cv2.rectangle(image, (startX, startY), (endX, endY), (0, 0, 255), 2)
		cv2.imwrite("out.png", image)
		print("Table coordinates: ({}, {}, {}, {})".format(startX, startY, endX, endY))
	return jsonify(todump)

@app.route('/orderParse')
def OParse3():
	json_data = request.args.get('Files')
	if(json_data is not None):
		data = ast.literal_eval(json_data)
	else:
		return jsonify('Input name error')
	fast = request.args.get('fast')
	if fast:
		return jsonify(OrderParserV3.order_parser("../uploads/"+data[0]).fast_cell_recognition())
	scale_data = request.args.get("scale")
	if(scale_data is not None):
		scale = ast.ast.literal_eval(scale_data)
	else:
		scale = 5
	table_bounding_box = request.args.get("table_box")
	if(table_bounding_box is not None):
		table_box = ast.literal_eval(table_bounding_box)
		op = orderParser(model_textbox,"../uploads/"+data[0],True,scale)
		op.no_sep_method(rect = table_box)
		op.OCR()
	else:
		op = orderParser(model_textbox,"../uploads/"+data[0],False,scale)
		if(op.largest_rect is None):return jsonify([])
	return jsonify(op.output_data())
			
@app.route('/dwgTojpg')
def dwgConvert():
	json_data = request.args.get('Files')
	if(json_data is not None):
		data = ast.literal_eval(json_data)
	else:
		return jsonify("Input name error")
	converterPath = "https://vector.express/api/v2/public/convert/dwg/cad2svg/svg/librsvg/pdf/gs/pdf?cad2pdf-auto-orientation=true&cad2pdf-auto-fit=true"
	out = {}
	for fname in data:
		filePath = f"../uploads/{fname}"
		out[fname] = []
		with open(filePath,'rb') as dwgFile:
			dwg_response = requests.post(converterPath,data = dwgFile)
			pdfUrl = json.loads(dwg_response.text)['resultUrl']
			pdf = requests.get(pdfUrl)
			pages = convert_from_bytes(pdf.content, 200)
			for page in pages:
				unique_name = str(uuid.uuid4())
				page.save(f'../uploads/{unique_name}.jpg', 'JPEG')
				out[fname].append(f'{unique_name}.jpg')
	return jsonify(out)
if __name__ == '__main__':
	app.run(host='0.0.0.0', port=8090, threaded=True)
    # app.run(host='0.0.0.0', port=6001, threaded=True)
