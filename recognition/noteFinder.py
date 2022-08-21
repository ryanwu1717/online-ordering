from thefuzz import fuzz
import numpy as np
from sklearn.cluster import DBSCAN
from collections import defaultdict
from torch import tensor
import cv2
import numpy as np
import merge2

def getNoteBox(wordlist,height,width):
	best_idx,best_rat = -1,0
	thres = 0.8
	poss_note_start = []
	for idx,word in enumerate(wordlist):
		rat = fuzz.ratio(word[1],"note")
		if(best_rat < rat):best_rat,best_idx = rat,idx
		if(rat > 88 and word[2].cpu() > 0.5):
			poss_note_start.append(idx)
			print(word,rat)
	#TLs,BRs = [],[]
	boxes = []
	for best_idx in poss_note_start:
		TL,BR = [wordlist[best_idx][0][0],wordlist[best_idx][0][1]],[wordlist[best_idx][0][2]+200,wordlist[best_idx][0][3]+30]
		(left,top),(right,bot) = TL,BR
		left = max(0,left)
		right = min(width,right)
		bot = min(height,bot)
		
		included,excluded = set(),set()
		for i in range(len(wordlist)):
			excluded.add(i)
		#max_box_width = 40
		y_steps,x_steps = 20,40
		while True:
			removed,diff = [],[]
			for idx in excluded:
				word = wordlist[idx]
				if(countIn(word,(left,top),(right,bot))):
					included.add(idx)
					removed.append(idx)
					left = min(left,word[0][0])
					top = min(top,word[0][1])
					#TLs.append((word[0][1],word[0][0]))
					right = max(right,word[0][2])
					bot = max(bot,word[0][3])
					#BRs.append((word[0][3],word[0][2]))
					diff.append(word[0][3]-word[0][1])
					#max_box_width = max(max_box_width,word[0][2]-word[0][0])
			if(removed):
				for rm in removed:
					excluded.remove(rm)
				#right += max_box_width*2
				right += x_steps
				bot += min(diff)
			else:
				break
		#TLs.append((top,left))
		#BRs.append((bot,right))
		boxes.append([[int(left),int(top)],[int(right),int(bot)]])
	#return TLs,BRs
	return boxes
def countIn(word,TL,BR):
	return (word[0][0] >= TL[0] and word[0][1] >= TL[1] and word[0][0] <= BR[0] and word[0][1] <= BR[1])
def getNoteBox_cluster(wordlist,height,width):
	X = np.array([[word[0][0],word[0][1]] for word in wordlist])
	clustering = DBSCAN(eps=width//30, min_samples=1, metric='chebyshev').fit(X)
	split_labels = defaultdict(list)
	for idx,lbl in enumerate(clustering.labels_):
		if(lbl == -1):continue
		split_labels[lbl].append(idx)
	TLs,BRs = [],[]
	boxes = None
	for label in split_labels:
		top,left,bot,right = height,width,0,0
		for index in split_labels[label]:
			left = min(wordlist[index][0][0],left)
			top = min(wordlist[index][0][1],top)
			right = max(wordlist[index][0][2],right)
			bot = max(wordlist[index][0][3],bot)
		if(boxes is None):
			boxes = np.array([[left,top,right,bot]])
		else:
			boxes = np.append(boxes,[[left,top,right,bot]],axis = 0)
	boxes = merge_second_pass(boxes)
	passed = []
	for box in boxes:
		wc = 0
		for word in wordlist:
			if(contains(word[0],box)):
				wc += 1
		if(wc <= 10):continue
		passed.append(box.tolist())
	return passed
def merge_second_pass(boxes):##Quadratic time complexity, small scale uses only
	x1 = boxes[:,0]
	y1 = boxes[:,1]
	x2 = boxes[:,2]
	y2 = boxes[:,3]
	area = (x2 - x1 + 1) * (y2 - y1 + 1)
	idxs = np.argsort(area)
	pick = []
	while len(idxs) > 0:
		last = len(idxs) - 1
		i = idxs[last]
		pick.append(i)
		dels = [last]
		for indices,idx in enumerate(idxs[:last]):
			if(contains(boxes[idx],boxes[i])):
				dels.append(indices)
				boxes[i][0] =  min(boxes[i][0],boxes[idx][0])
				boxes[i][1] =  min(boxes[i][1],boxes[idx][1])  
				boxes[i][2] =  max(boxes[i][2],boxes[idx][2])  
				boxes[i][3] =  max(boxes[i][3],boxes[idx][3])
		idxs = np.delete(idxs,dels)
	return boxes[pick]
def contains(bb1,bb2):
	l1,r1,l2,r2 = bb1[0],bb1[2],bb2[0],bb2[2]
	t1,b1,t2,b2 = bb1[1],bb1[3],bb2[1],bb2[3]
	return l1 < r2 and r1 > l2 and t1 < b2 and b1 > t2