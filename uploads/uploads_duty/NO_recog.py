import imageio
import numpy as np
from PIL import Image
import cv2
import math
import pytesseract
import sys
import glob, os
# pytesseract.pytesseract.tesseract_cmd = 'C:/OCR/Tesseract-OCR/tesseract.exe'
def testBlack(red,green,blue):
	if red < 210 and green < 210 and blue < 210 and abs(int(red)-int(green)) < 100 and abs(int(red)-int(blue)) < 100 and abs(int(green)-int(blue)) < 100:
		return True
	else:
		return False
def getNO(route):

	custom_oem_psm_config = r'--psm 7'
	OCRTest = Image.open(route)
	im_N = np.array(OCRTest)
	BP_d_x = im_N.shape[1]
	BP_d_y = im_N.shape[0]
	OCRTest2 = np.zeros([BP_d_y,BP_d_x,3])
	for i in range(BP_d_x):
		for j in range(BP_d_y):
			if not testBlack(im_N[j,i,2],im_N[j,i,1],im_N[j,i,0]):
				OCRTest2[j,i,:] = [255,255,255]
			else:
				OCRTest2[j,i,:] = [0,0,0]
	OCRTest2 = OCRTest2.astype(np.uint8)
	OCRTest2 = Image.fromarray(OCRTest2)
	return (pytesseract.image_to_string(OCRTest2,config=custom_oem_psm_config))


#main
route = 'NO.png'
print(getNO(route))

