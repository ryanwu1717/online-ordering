import imageio
import numpy as np
from PIL import Image, ImageOps, ImageDraw
import cv2
import math
import pytesseract
import sys
from scipy.ndimage import morphology, label
import scipy.ndimage as ndimage

x=15000
sys.setrecursionlimit(x)
image = None
canvas = None
threshold = None
size_x = 0
size_y = 0
all_box = []

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
			#print("Down found",[x,y])
			x = x+dirX*count
			y = y+dirY*count
			break
		elif testBlack(imageR[y-dirY*count][x-dirX*count],imageG[y-dirY*count][x-dirX*count],imageB[y-dirY*count][x-dirX*count]):
			#print("Up found",[x,y])
			x = x-dirX*count
			y = y-dirY*count
			break
		count += 1
	return [x,y]

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

def getConnectedShape_Opti(x,y,call_i,call_j):
	global image 
	global canvas 
	global threshold 
	print((x,y,threshold,call_i,call_j),file=sys.stderr)
	d_x = image.shape[1]
	d_y = image.shape[0]
	if(call_i == 0 and call_j == 0):
		for i in range(-threshold,threshold+1,3):
			for j in range(-threshold,threshold+1,3):
				if(x+i > 0 and y+j > 0 and x+i < d_x and y+j < d_y and (i !=0 or j !=0)):
					if canvas[y+j][x+i] == 255:
						if image[y+j][x+i][0] < 210:
							canvas[y+j][x+i] = 120
							image[y+j][x+i][:] = 255
							#print(i,j)
							getConnectedShape_Opti(x+i,y+j,i,j)
	
	else:
		if(call_i < 0):
			for i in range(x-threshold,x-threshold-call_i):
				if(call_j < 0):
					for j in range(y-threshold,y+threshold+3):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(i,j,i-x,j-y)
				else:
					for j in range(y-threshold,y+threshold+3):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(i,j,i-x,j-y)
			if(call_j < 0):
				for i in range(x-call_i-threshold,x+threshold+3):
						for j in range(y-threshold,y-call_j-threshold):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(i,j,i-x,j-y)

			else:
				for i in range(x-call_i-threshold,x+threshold+3):
						for j in range(y+threshold+3-call_j,y+threshold+3):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(i,j,i-x,j-y)
		else :
			for i in range(x+threshold-call_i+3,x+threshold+3):
				if(call_j < 0):
					for j in range(y-threshold,y+threshold+3):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(i,j,i-x,j-y)
				else:
					for j in range(y-threshold,y+threshold+3):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 120
									image[j][i][:] = 255
									getConnectedShape_Opti(i,j,i-x,j-y)
			if(call_j < 0):
				for i in range(x-call_i-threshold,x+threshold+3):
						for j in range(y-threshold,y-call_j-threshold):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(i,j,i-x,j-y)
			else:
				for i in range(x-call_i-threshold,x+threshold+3):
						for j in range(y+threshold+3-call_j,y+threshold+3):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 120
										image[j][i][:] = 255
										getConnectedShape_Opti(i,j,i-x,j-y)
	return
def naiveInter(image,scale):
	d_x = image.shape[1]
	d_y = image.shape[0]
	#print("scale:",scale,"x:",d_x,"y:",d_y)
	output  = np.full([math.floor(d_y*scale+4),math.floor(d_x*scale+4)],255)
	for i in range(d_x):
		for j in range(d_y):
			if(image[j,i] == 120):
				#if(j*scale+scale*4 < d_y*scale and i*scale+scale*4 < d_x):
				output[j*scale:j*scale+scale*4,i*scale:i*scale+scale*4] = image[j,i]
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
def box(orig):
    img = ImageOps.grayscale(orig)
    im = np.array(img)

    # Inner morphological gradient.
    im = morphology.grey_dilation(im, (3, 3)) - im

    # Binarize.
    mean, std = im.mean(), im.std()
    t = mean + std
    im[im < t] = 0
    im[im >= t] = 1

    # Connected components.
    lbl, numcc = label(im)
    # Size threshold.
    min_size = 200 # pixels
    box = []
    for i in range(1, numcc + 1):
        py, px = np.nonzero(lbl == i)
        if len(py) < min_size or px.max() - px.min() <100 or py.max() - py.min() < 100 :
            im[lbl == i] = 0
            continue

        xmin, xmax, ymin, ymax = px.min(), px.max(), py.min(), py.max()
        # Four corners and centroid.
        box.append([xmin.item(), ymin.item(), xmax.item()-xmin.item(), ymax.item()-ymin.item()])

    return im.astype(np.uint8) * 255, box
def getPix(x,pos):
    if(x.size > 2000):
        xs = pos%size_x
        global all_box
        all_box.append([xs.min().item(),math.floor(pos.min()/size_x),xs.max().item()-xs.min().item(),math.floor(pos.max()/size_x)-math.floor(pos.min()/size_x)])
def box_v2(im):
	im = morphology.grey_dilation(im, (3, 3)) - im
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
	lbls = np.arange(1, numcc + 1)
	ndimage.labeled_comprehension(im, lbl, lbls, getPix, float, -1, True)

def getParts(filename,area,im,rotate):
	# area = [[101,233],[3209,2117]]
	#filename = "cust.png"
	global all_box
	all_box = []
	filepath = "../uploads/"
	reduction = 8
	BPImg = np.array(im)
	BPImg = BPImg[area[0][1]:area[1][1],area[0][0]:area[1][0]]
	BP_d_x = BPImg.shape[1]
	BP_d_y = BPImg.shape[0]
	BPImg = cv2.cvtColor(BPImg.astype('uint8'), cv2.COLOR_BGR2GRAY)
	ret,BPImg = cv2.threshold(BPImg,170,255,cv2.THRESH_BINARY)
	BPImg = np.repeat(BPImg[:, :, np.newaxis], 3, axis=2)
	BPImg[0:reduction,:,:] = [255,255,255]
	BPImg[:,0:reduction,:] = [255,255,255]
	BPImg[BP_d_y-(reduction):BP_d_y,:,:] = [255,255,255]
	BPImg[:,BP_d_x-(reduction):BP_d_x,:] = [255,255,255]

	naive_red = naiveReduction(BPImg,reduction)
	BPImg_reduced = naive_red
	BPCanvas = np.full([math.floor(BP_d_y/reduction),math.floor(BP_d_x/reduction)],255)
	BPFullCanvas = np.full([BP_d_y,BP_d_x],255)
	im = cv2.cvtColor(im, cv2.COLOR_BGR2RGB)
	im = Image.fromarray(im)
	global size_x,size_y
	(size_x,size_y) = im.size
	img = ImageOps.grayscale(im)
	im = np.array(img)
	box_v2(im)
	# boxes = []
	# for i in range(math.floor(BP_d_x/reduction)):
	# 	for j in range(math.floor(BP_d_y/reduction)):
	# 		#print([i,j])
	# 		if BPImg_reduced[j,i,0] < 210:
	# 			#print([i,j])
	# 			BPCanvas = np.full([math.ceil(BP_d_y/reduction),math.ceil(BP_d_x/reduction)],255)
	# 			global image 
	# 			global canvas 
	# 			global threshold
	# 			threshold = 1
	# 			canvas = BPCanvas
	# 			image = BPImg_reduced
	# 			getConnectedShape_Opti(i,j,0,0)
	# 			count = np.count_nonzero(BPCanvas == 120)
	# 			if count > 250:
	# 				nons = np.nonzero(BPCanvas == 120)
	# 				TLY = min(nons[0])
	# 				TLX = min(nons[1])
	# 				BRY = max(nons[0])
	# 				BRX = max(nons[1])
	# 				boxes.append([TLX.item()*reduction+area[0][0],TLY.item()*reduction+area[0][1],(BRX.item()-TLX.item())*reduction,(BRY.item()-TLY.item())*reduction])
	# 				#print([TLX,TLY],[BRX,BRY])

	return all_box

"""
area = [[101,233],[3209,2117]]
filename = "cust.png"
filepath = "../uploads/"
reduction = 8
im = imageio.imread(filepath+filename)##'111.jpg'
BPImg = np.array(im)
BPImg = BPImg[area[0][1]:area[1][1],area[0][0]:area[1][0]]
BP_d_x = BPImg.shape[1]
BP_d_y = BPImg.shape[0]
BPImg = cv2.cvtColor(BPImg.astype('uint8'), cv2.COLOR_BGR2GRAY)
ret,BPImg = cv2.threshold(BPImg,170,255,cv2.THRESH_BINARY)
BPImg = np.repeat(BPImg[:, :, np.newaxis], 3, axis=2)
BPImg[0:reduction,:,:] = [255,255,255]
BPImg[:,0:reduction,:] = [255,255,255]
BPImg[BP_d_y-(reduction):BP_d_y,:,:] = [255,255,255]
BPImg[:,BP_d_x-(reduction):BP_d_x,:] = [255,255,255]

naive_red = naiveReduction(BPImg,reduction)
BPImg_reduced = naive_red
BPCanvas = np.full([math.floor(BP_d_y/reduction),math.floor(BP_d_x/reduction)],255)
BPFullCanvas = np.full([BP_d_y,BP_d_x],255)

testC = 1
for i in range(math.floor(BP_d_x/reduction)):
	for j in range(math.floor(BP_d_y/reduction)):
		#print([i,j])
		if BPImg_reduced[j,i,0] < 210:
			#print([i,j])
			BPCanvas = np.full([math.ceil(BP_d_y/reduction),math.ceil(BP_d_x/reduction)],255)
			getConnectedShape_Opti(BPImg_reduced,BPCanvas,i,j,1,0,0)
			testC += 1
			count = np.count_nonzero(BPCanvas == 120)
			if count > 250:
				nons = np.nonzero(BPCanvas == 120)
				TLY = min(nons[0])
				TLX = min(nons[1])
				BRY = max(nons[0])
				BRX = max(nons[1])
				print([TLX,TLY],[BRX,BRY])
"""
