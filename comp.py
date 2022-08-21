from __future__ import print_function
import cv2 as cv
import numpy as np
import argparse
def match(file1,file2):
	#---Main---#
	parser = argparse.ArgumentParser(description='Code for Feature Matching with FLANN tutorial.')
	parser.add_argument('--input1', help='Path to input image 1.', default='shapes/ShapeAt(2,375).png')
	parser.add_argument('--input2', help='Path to input image 2.', default='shapes/Comp2.png')
	args = parser.parse_args()

	#img1 = cv.imread(cv.samples.findFile(args.input1), cv.IMREAD_GRAYSCALE)
	#img2 = cv.imread(cv.samples.findFile(args.input2), cv.IMREAD_GRAYSCALE)
	img1 = cv.imread(args.input1, cv.IMREAD_GRAYSCALE)
	img2 = cv.imread(args.input2, cv.IMREAD_GRAYSCALE)
	dim = img2.shape
	dim_thres = dim[0]*dim[1]/10000
	if img1 is None or img2 is None:
	    print('Could not open or find the images!')
	    exit(0)

	#-- Step 1: Detect the keypoints using SURF Detector, compute the descriptors
	minHessian = 400
	detector = cv.xfeatures2d_SURF.create(hessianThreshold=minHessian)
	keypoints1, descriptors1 = detector.detectAndCompute(img1, None)
	keypoints2, descriptors2 = detector.detectAndCompute(img2, None)

	#-- Step 2: Matching descriptor vectors with a FLANN based matcher
	# Since SURF is a floating-point descriptor NORM_L2 is used
	matcher = cv.DescriptorMatcher_create(cv.DescriptorMatcher_FLANNBASED)
	knn_matches = matcher.knnMatch(descriptors1, descriptors2, 2)

	#-- Filter matches using the Lowe's ratio test
	ratio_thresh = 0.7
	good_matches = []
	G_match = 0
	B_match = 0
	for m,n in knn_matches:
	    if m.distance < ratio_thresh * n.distance:
	    	G_match += 1
	    	good_matches.append(m)
	    else:
	    	B_match += 1

	points = []
	for match in good_matches:
		n_pt = keypoints1[match.queryIdx].pt
		tooClose = False
		for pts in points:
			dist = (n_pt[0]-pts[0])**2+(n_pt[1]-pts[1])**2
			if(dist < 1000):
				tooClose = True
				break
		if(not tooClose):
			points.append(n_pt)
			#print(n_pt-pts)
		#print(keypoints1[match.queryIdx].pt)
		#print(keypoints2[match.trainIdx].pt)
	
	#print(keypoints1[good_matches[5].trainIdx])
	#print("Good:",G_match,"Bad",B_match,"Percent",G_match/(G_match+B_match)*100,"%")
	#print(len(points),len(good_matches),dim_thres)
	#-- Draw matches
	#img_matches = np.empty((max(img1.shape[0], img2.shape[0]), img1.shape[1]+img2.shape[1], 3), dtype=np.uint8)
	#cv.drawMatches(img1, keypoints1, img2, keypoints2, good_matches, img_matches, flags=cv.DrawMatchesFlags_NOT_DRAW_SINGLE_POINTS)
	#cv.drawMatches(img1, keypoints1, img2, keypoints2, good_matches, img_matches, flags=cv.DrawMatchesFlags_DRAW_RICH_KEYPOINTS)
	#-- Show detected matches

	if(len(points) > dim_thres):
		#cv.imshow('Good Matches', img_matches)
		#cv.waitKey(0)
		return True,len(points)/dim_thres
	else:
		return False,len(points)/dim_thres
		#print("No Match")