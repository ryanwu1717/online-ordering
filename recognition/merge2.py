# import the necessary packages
import numpy as np
# Malisiewicz et al.
def non_max_suppression_fast(boxes, overlapThresh, mergingThresh = 1):
	# if there are no boxes, return an empty list
	if len(boxes) == 0:
		return []
	# if the bounding boxes integers, convert them to floats --
	# this is important since we'll be doing a bunch of divisions
	if boxes.dtype.kind == "i":
		boxes = boxes.astype("float")
	# initialize the list of picked indexes	
	pick = []
	# grab the coordinates of the bounding boxes
	#print(boxes)
	x1 = boxes[:,0]
	y1 = boxes[:,1]
	x2 = boxes[:,2]
	y2 = boxes[:,3]
	# compute the area of the bounding boxes and sort the bounding
	# boxes by the bottom-right y-coordinate of the bounding box
	area = (x2 - x1 + 1) * (y2 - y1 + 1)
	idxs = np.argsort(y2)
	# keep looping while some indexes still remain in the indexes
	# list
	while len(idxs) > 0:
		# grab the last index in the indexes list and add the
		# index value to the list of picked indexes
		last = len(idxs) - 1
		i = idxs[last]
		pick.append(i)
		# find the largest (x, y) coordinates for the start of
		# the bounding box and the smallest (x, y) coordinates
		# for the end of the bounding box
		xx1 = np.maximum(x1[i], x1[idxs[:last]])
		yy1 = np.maximum(y1[i], y1[idxs[:last]])
		xx2 = np.minimum(x2[i], x2[idxs[:last]])
		yy2 = np.minimum(y2[i], y2[idxs[:last]])
		# compute the width and height of the bounding box
		## +1 changed to +10 for increased near-miss boxes merging behaviour
		w = np.maximum(0, xx2 - xx1 + mergingThresh)
		h = np.maximum(0, yy2 - yy1)
		# compute the ratio of overlap
		overlap = (w * h) / area[idxs[:last]]
		overlapped = np.concatenate(([last],np.where(overlap > overlapThresh)[0]))

		"""
		xxx1 = np.maximum(x1[i], x1[idxs[overlapped]])
		yyy1 = np.maximum(y1[i], y1[idxs[overlapped]])
		xxx2 = np.minimum(x2[i], x2[idxs[overlapped]])
		yyy2 = np.minimum(y2[i], y2[idxs[overlapped]])
		"""

		xxx1 = min([x1[j] for j in [idxs[k] for k in overlapped]])
		yyy1 = min([y1[j] for j in [idxs[k] for k in overlapped]])
		xxx2 = max([x2[j] for j in [idxs[k] for k in overlapped]])
		yyy2 = max([y2[j] for j in [idxs[k] for k in overlapped]])
		#print("------------------")
		#print(x1[i], [x1[j] for j in [idxs[k] for k in overlapped]])
		#print(xxx1)
		#print([xxx1[0],yyy1[0],xxx2[0],yyy2[0]])
		#print(boxes[i])
		boxes[i] = [xxx1,yyy1,xxx2,yyy2]
		#print(boxes[i])
		# delete all indexes from the index list that have
		idxs = np.delete(idxs, np.concatenate(([last],
			np.where(overlap > overlapThresh)[0])))
	# return only the bounding boxes that were picked using the
	# integer data type
	return boxes[pick].astype("int")