import imageio
import numpy as np
from PIL import Image
import cv2
import math
# import pytesseract
import sys
import NO_recog
from flask import Flask
from flask import jsonify
from flask import request

x=15000
sys.setrecursionlimit(x)
app = Flask(__name__)


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
		y = y+dir*5
		while True:
			#print("now at",[x,y])
			if (testBlack(imageR[y][x+5],imageG[y][x+5],imageB[y][x+5]) and testBlack(imageR[y][x+4],imageG[y][x+4],imageB[y][x+4]) and testBlack(imageR[y][x+3],imageG[y][x+3],imageB[y][x+3]) and testBlack(imageR[y][x+2],imageG[y][x+2],imageB[y][x+2]) and testBlack(imageR[y][x+1],imageG[y][x+1],imageB[y][x+1])) or (testBlack(imageR[y][x-5],imageG[y][x-5],imageB[y][x-5]) and testBlack(imageR[y][x-4],imageG[y][x-4],imageB[y][x-4]) and testBlack(imageR[y][x-3],imageG[y][x-3],imageB[y][x-3]) and testBlack(imageR[y][x-2],imageG[y][x-2],imageB[y][x-2]) and testBlack(imageR[y][x-1],imageG[y][x-1],imageB[y][x-1])) :
				break
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
		x = x+dir*5
		while True:
			#print("now at",[x,y])
			if (testBlack(imageR[y+5][x],imageG[y+5][x],imageB[y+5][x]) and testBlack(imageR[y+4][x],imageG[y+4][x],imageB[y+4][x]) and testBlack(imageR[y+3][x],imageG[y+3][x],imageB[y+3][x]) and testBlack(imageR[y+2][x],imageG[y+2][x],imageB[y+2][x]) and testBlack(imageR[y+1][x],imageG[y+1][x],imageB[y+1][x])) or (testBlack(imageR[y-5][x],imageG[y-5][x],imageB[y-5][x]) and testBlack(imageR[y-4][x],imageG[y-4][x],imageB[y-4][x]) and testBlack(imageR[y-3][x],imageG[y-3][x],imageB[y-3][x]) and testBlack(imageR[y-2][x],imageG[y-2][x],imageB[y-2][x]) and testBlack(imageR[y-1][x],imageG[y-1][x],imageB[y-1][x])) :
				break
			x = x + dir
			if not testBlack(imageR[y][x],imageG[y][x],imageB[y][x]):
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
	d_x = image.shape[1]
	d_y = image.shape[0]
	if(call_i == 0 and call_j == 0):
		for i in range(-threshold,threshold+1,1):
			for j in range(-threshold,threshold+1,1):
				if(x+i > 0 and y+j > 0 and x+i < d_x and y+j < d_y and (i !=0 or j !=0)):
					if canvas[y+j][x+i] == 255:
						if image[y+j][x+i][0] < 210:
							canvas[y+j][x+i] = 0
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
									canvas[j][i] = 0
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
				else:
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 0
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
			if(call_j < 0):
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y-threshold,y-call_j-threshold):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 0
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)

			else:
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y+threshold+1-call_j,y+threshold+1):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 0
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
		else :
			for i in range(x+threshold-call_i+1,x+threshold+1):
				if(call_j < 0):
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 0
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
				else:
					for j in range(y-threshold,y+threshold+1):
						if(i > 0 and j > 0 and i < d_x and j < d_y):
							if canvas[j][i] == 255:
								if image[j][i][0] < 210:
									canvas[j][i] = 0
									image[j][i][:] = 255
									getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
			if(call_j < 0):
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y-threshold,y-call_j-threshold):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 0
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)
			else:
				for i in range(x-call_i-threshold,x+threshold+1):
						for j in range(y+threshold+1-call_j,y+threshold+1):
							if(i > 0 and j > 0 and i < d_x and j < d_y):
								if canvas[j][i] == 255:
									if image[j][i][0] < 210:
										canvas[j][i] = 0
										image[j][i][:] = 255
										getConnectedShape_Opti(image,canvas,i,j,threshold,i-x,j-y)


	return

@app.route('/cut')
def main0():
	#Main
	filename = request.args.get('filename')
	im = imageio.imread(filename)##'111.jpg'
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

	flag = False
	for i in range(d_x):
		for j in range(d_y):
			if im_r[j][i] < 210 and im_g[j][i] < 210 and im_b[j][i] < 210:
				flag = True
				firstGuess = [i,j]
				break
		if flag == True:
			break
	flag = False
	for j in range(d_y):
		for i in range(d_x):
			if im_r[j][i] < 210 and im_g[j][i] < 210 and im_b[j][i] < 210:
				flag = True
				secondGuess = [i,j]
				break
			if flag == True:
				break
	firstSum = firstGuess[0]+firstGuess[1]
	secondSum = secondGuess[0]+secondGuess[1]
	if firstSum > secondSum :
		topLeft = secondGuess
	else :
		topLeft = firstGuess
	print("TL",topLeft)

	flag = False
	for i in range(d_x-1,-1,-1):
		for j in range(d_y-1,-1,-1):
			if testBlack(im_r[j][i],im_g[j][i],im_b[j][i]):
				flag = True
				firstGuess = [i,j]
				break
		if flag == True:
			break
	flag = False
	for j in range(d_y-1,-1,-1):
		for i in range(d_x-1,-1,-1):
			if testBlack(im_r[j][i],im_g[j][i],im_b[j][i]):
				flag = True
				secondGuess = [i,j]
				break
			if flag == True:
				break
	firstSum = firstGuess[0]+firstGuess[1]
	secondSum = secondGuess[0]+secondGuess[1]
	if firstSum < secondSum :
		botRight = secondGuess
	else :
		botRight = firstGuess

	flag = False
	for i in range(d_x):
		for j in range(d_y-1,-1,-1):
			if im_r[j][i] < 210 and im_g[j][i] < 210 and im_b[j][i] < 210:
				flag = True
				firstGuess = [i,j]
				break
		if flag == True:
			break
	flag = False
	for j in range(d_y-1,-1,-1):
		for i in range(d_x):
			if im_r[j][i] < 210 and im_g[j][i] < 210 and im_b[j][i] < 210:
				flag = True
				secondGuess = [i,j]
				break
			if flag == True:
				break
	firstSum = firstGuess[0]+firstGuess[1]
	secondSum = secondGuess[0]+secondGuess[1]
	if firstSum < secondSum :
		botLeft = secondGuess
	else :
		botLeft = firstGuess


	outputImg = np.zeros([d_y,d_x,3])
	outputImg[:,:,0] = im_b
	outputImg[:,:,1] = im_g
	outputImg[:,:,2] = im_r
	rise = botLeft[1]-topLeft[1]
	run = botLeft[0]-topLeft[0]
	rAngle = np.arctan(rise/run)*180/np.pi
	outputImg = rotateImage(outputImg,rAngle-90)
	cv2.imwrite('rot.png', outputImg)
	new_b = outputImg[:,:,0]
	new_g = outputImg[:,:,1]
	new_r = outputImg[:,:,2]
	flag = False
	for i in range(0,d_x,3):
		for j in range(0,d_y,3):
			if testBlack(new_r[j][i],new_g[j][i],new_b[j][i]):
				flag = True
				firstGuess = [i,j]
				break
		if flag == True:
			break
	flag = False
	for j in range(0,d_y,3):
		for i in range(0,d_x,3):
			if testBlack(new_r[j][i],new_g[j][i],new_b[j][i]):
				flag = True
				secondGuess = [i,j]
				break
		if flag == True:
			break
	firstSum = firstGuess[0]+firstGuess[1]
	secondSum = secondGuess[0]+secondGuess[1]
	if firstSum > secondSum :
		topLeft = secondGuess
	else :
		topLeft = firstGuess

	nextPos = moveHor(new_r,new_g,new_b,topLeft,1,1)
	nextPos = moveVer(new_r,new_g,new_b,nextPos,1,3)
	print(nextPos)
	BPTopLeft = nextPos
	nextPos = moveHor(new_r,new_g,new_b,nextPos,1,4)
	nextPos = moveVer(new_r,new_g,new_b,nextPos,1,1)
	print(nextPos)
	BPBotRight = nextPos

	BP_d_x = BPBotRight[0]-BPTopLeft[0]
	BP_d_y = BPBotRight[1]-BPTopLeft[1]
	BPImg = np.zeros([BP_d_y,BP_d_x,3])
	Splits = np.zeros([BP_d_y,BP_d_x,3])
	BPImg[:,:,0] = new_b[BPTopLeft[1]:BPBotRight[1],BPTopLeft[0]:BPBotRight[0]]
	BPImg[:,:,1] = new_g[BPTopLeft[1]:BPBotRight[1],BPTopLeft[0]:BPBotRight[0]]
	BPImg[:,:,2] = new_r[BPTopLeft[1]:BPBotRight[1],BPTopLeft[0]:BPBotRight[0]]
	cv2.imwrite('BP.png', BPImg)
	BPImg[0:5,:,:] = 255;
	BPImg[:,0:5,:] = 255;
	BPImg[BP_d_y-5:BP_d_y,:,:] = 255;
	BPImg[:,BP_d_x-5,BP_d_x:] = 255;
	for i in range(BP_d_x):
		for j in range(BP_d_y):
			if not testBlack(new_r[j+BPTopLeft[1],i+BPTopLeft[0]],new_g[j+BPTopLeft[1],i+BPTopLeft[0]],new_b[j+BPTopLeft[1],i+BPTopLeft[0]]):
				BPImg[j,i,:] = [255,255,255]
			else:
				BPImg[j,i,:] = [0,0,0]
	BPImg[0:5,:,:] = [255,255,255]
	BPImg[:,0:5,:] = [255,255,255]
	BPImg[BP_d_y-5:BP_d_y,:,:] = [255,255,255]
	BPImg[:,BP_d_x-5:BP_d_x,:] = [255,255,255]
	cv2.imwrite(filename, BPImg)#noncolorful cutPic
	BPCanvas = np.full([BP_d_y,BP_d_x],255)

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
	cv2.imwrite(serial, NOImg)
	return serial
@app.route('/recog')
def main1():
	filename = request.args.get('filename')
	return(NO_recog.getNO(filename))
if __name__ == '__main__':
    app.run(host='127.0.0.1', port=8090)