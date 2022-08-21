import imageio
import numpy as np
from PIL import Image
import cv2
import math
import pytesseract
import sys
import glob, os
import pathlib
pathlib.Path(__file__).parent.absolute()


#main
def Recog(Fname):
	#Fname = 'shapes/test.png'
	#Fname = 'shapes/Comp.png'
	path = "../uploads/"
	custom_oem_psm_config = r'--psm 11 digits'
	OCRTest = Image.open(path+Fname)
	data = pytesseract.image_to_data(OCRTest,config=custom_oem_psm_config)
	data = data.split("\n")
	data.pop(0)
	data.pop()
	box_list_new = []
	for st in data:
		li = st.split("\t")
		if(len(li[11]) != 0):
			box_list_new.append([li[6],li[7],li[8],li[9]])
	im = Image.open(path+"/"+Fname)
	im_N = np.array(im)
	im_Box = im_N.copy()

	for crds in box_list_new:
		cv2.rectangle(im_Box,(int(crds[0]),int(crds[1])),(int(crds[0])+int(crds[2]),int(crds[1])+int(crds[3])),(255,0,0),2)
		#cv2.rectangle(im_N,(int(crds[0]),int(crds[1])),(int(crds[0])+int(crds[2]),int(crds[1])+int(crds[3])),(255,255,255),-1)
	#cv2.imwrite('shapes/recog.png', im_N)
	cv2.imwrite(path+"recog_"+Fname, im_Box)
	return box_list_new
