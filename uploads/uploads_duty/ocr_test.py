# coding=UTF-8
from __future__ import print_function
from common import anorm, getsize
import easyocr
import json
from flask import Flask
from flask import jsonify
from flask import request
import os
import cv2
import numpy as np
from imutils.perspective import four_point_transform
from imutils import contours
import argparse
import imutils
from PIL import Image
os.environ["KMP_DUPLICATE_LIB_OK"] = "TRUE"


# need to run only once to load model into memory
reader = easyocr.Reader(['en'])
app = Flask(__name__)


@app.route("/")
def home():
    imgname = r"C:\\Users\\admin\\Documents\\mil\\uploads\\0007-1.png"
    # img = cv2.imread(imgname)

    # # 二階化處理
    # gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    # blurred = cv2.GaussianBlur(gray, (5, 5), 0)
    # edged = cv2.Canny(blurred, 3, 4)
    # # edged = cv2.resize(edged, (1170,1645))
    # # 尋找邊緣
    # cnts = cv2.findContours(
    #     edged.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    # cnts = imutils.grab_contours(cnts)
    # docCnt = None

    # # 處理邊緣
    # if len(cnts) > 0:
    #     cnts = sorted(cnts, key=cv2.contourArea, reverse=True)
    #     for c in cnts:
    #         # (cx, cy, cw, ch) = cv2.boundingRect(c)
    #         # self.x = self.x - cx
    #         # self.y = self.y - cy
    #         # self.x = self.x
    #         # self.y = self.y
    #         peri = cv2.arcLength(c, True)
    #         approx = cv2.approxPolyDP(c, 0.02 * peri, True)

    #         if len(approx) == 4:
    #             docCnt = approx
    #             break

    # # 將考卷定位並裁切
    # # warped = cv2.resize(gray, (1170,1645))
    # warped = four_point_transform(gray, docCnt.reshape(4, 2))
    # cv2.imshow('1', warped)
    # cv2.waitKey(0)
    # cv2.destroyAllWindows()
    data=request.args
    filename=data['filename']
    result = reader.readtext(
        r"C:\Users\admin\Documents\mil\uploads\\"+filename, detail=0)
    # for x in range(len(result)):
    #     result[x] = str(x)+":"+result[x]
    # result = {'0':'111','4':'222'}
    print(result)
    jsonResult = json.dumps({"result": result})
    return jsonResult
    # str1 = ""
    # for ele in result:
    #     str1 += ele
    # return jsonify(result)


@app.route('/match')

def match():
    data = request.args
    img1 = cv2.imread(data['img1'], 0)
    img2 = cv2.imread(data['img2'], 0)
    feature_name = 'sift'
    detector, matcher = init_feature(feature_name)

    if img1 is None:
        print('Failed to load fn1:', fn1)
        sys.exit(1)

    if img2 is None:
        print('Failed to load fn2:', fn2)
        sys.exit(1)

    if detector is None:
        print('unknown feature:', feature_name)
        sys.exit(1)

    print('using', feature_name)

    kp1, desc1 = detector.detectAndCompute(img1, None)
    kp2, desc2 = detector.detectAndCompute(img2, None)
    print('img1 - %d features, img2 - %d features' % (len(kp1), len(kp2)))

    def match_and_draw(win):
        status=[]
        raw_matches = matcher.knnMatch(desc1, trainDescriptors = desc2, k = 2) #2
        p1, p2, kp_pairs = filter_matches(kp1, kp2, raw_matches)
        if len(p1) >= 4:
            H, status = cv2.findHomography(p1, p2, cv2.RANSAC, 5.0)
            print(status)
            return np.sum(status) * 100 / len(status)
        return 0

        # _vis = explore_match(win, img1, img2, kp_pairs, status, H)
    return json.dumps(match_and_draw('find_obj'))
    # cv2.waitKey()
    # cv2.destroyAllWindows()
FLANN_INDEX_KDTREE = 1  # bug: flann enums are missing
FLANN_INDEX_LSH = 6


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




if __name__ == '__main__':
    app.run(host='127.0.0.1', port=8090)
