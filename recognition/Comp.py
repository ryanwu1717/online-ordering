from __future__ import print_function
import cv2 as cv
import numpy as np
import argparse
def compare(fn1,fn2,hess,nL,thres):
	#---Main---#
	#parser = argparse.ArgumentParser(description='Code for Feature Matching with FLANN tutorial.')
	#parser.add_argument('--input1', help='Path to input image 1.', default='shapes/ShapeAt(2,375).png')
	#parser.add_argument('--input2', help='Path to input image 2.', default='shapes/Comp2.png')
	#args = parser.parse_args()

	#img1 = cv.imread(cv.samples.findFile(args.input1), cv.IMREAD_GRAYSCALE)
	#img2 = cv.imread(cv.samples.findFile(args.input2), cv.IMREAD_GRAYSCALE)
	img1 = cv.imread(fn1, cv.IMREAD_GRAYSCALE)
	img2 = cv.imread(fn2, cv.IMREAD_GRAYSCALE)
	dim = img2.shape
	dim2 = img1.shape
	dim_thres = (dim[0]*dim[1]+dim2[0]*dim2[1])/(10000*thres)
	#print(dim_thres)
	#dim_thres = dim[0]*dim[1]/10000
	#dim_thres = 1
	if img1 is None or img2 is None:
	    print('Could not open or find the images!')
	    exit(0)

	#-- Step 1: Detect the keypoints using SURF Detector, compute the descriptors
	#minHessian = 400
	#detector = cv.xfeatures2d_SURF.create(hessianThreshold=minHessian,nOctaveLayers = 1)
	minHessian = hess
	detector = cv.xfeatures2d_SURF.create(hessianThreshold=minHessian,nOctaveLayers = nL)
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
	slope_pts = []
	all_slopes = []
	for match in good_matches:
		n_pt = keypoints1[match.queryIdx].pt
		m_pt = keypoints2[match.trainIdx].pt
		slope_pts.append([n_pt,m_pt])
		#print(n_pt,m_pt)
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
	for i in range(len(slope_pts)):
		tmp = []
		for j in range(len(slope_pts)):
			if(slope_pts[i][0][0] != slope_pts[j][0][0] and slope_pts[i][1][0] != slope_pts[j][1][0]):	
				slope1 = round((slope_pts[i][0][1] - slope_pts[j][0][1])/(slope_pts[i][0][0] - slope_pts[j][0][0]),2)
				slope2 = round((slope_pts[i][1][1] - slope_pts[j][1][1])/(slope_pts[i][1][0] - slope_pts[j][1][0]),2)
				tmp.append([slope1,slope2])
		all_slopes.append(tmp)
		#print(slope_pts[i][0][0],slope_pts[i][1])
		#print(slope_pts[i])


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
		#print("matched")
		return(True,len(points)/dim_thres)
	else:
		#print("No Match")
		return(False,len(points)/dim_thres)