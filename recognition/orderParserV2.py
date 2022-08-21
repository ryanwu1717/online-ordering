from itertools import zip_longest
from collections import OrderedDict,defaultdict
import cv2
import os
import sys
import pytesseract
from PIL import Image
import torch.backends.cudnn as cudnn
import torch.utils.data
import torch.nn.functional as torchF
import craft_utils
import imgproc
from craft import CRAFT
import numpy as np
import time
from torch.autograd import Variable

class orderParser():
    def __init__(self,net,file,no_sep_case = False,scale = 5) -> None:
        self.model_textbox = net
        self.image_route = file
        self.image = cv2.imread(self.image_route, cv2.IMREAD_GRAYSCALE)
        self.row_bounds = []
        self.col_bounds = []
        self.no_sep_case = no_sep_case
        _,self.text_bounding_boxes,_ = getTextBox(self.model_textbox,imgproc.loadImage(self.image_route))
        self.cell_bounding_boxes,self.largest_rect = extract_cell_images_from_table(self.image,scale)
        if(self.largest_rect is not None and not no_sep_case):
            heights = [x[0][3] for x in [row for row in self.cell_bounding_boxes]]
            heights.sort()
            self.median = heights[len(heights)//2]
            self.col_calc(col_constraints=[])
            self.row_calc(row_constraints=[],use_textbox=False)
            # print(self.row_bounds,self.col_bounds)
            self.OCR()
            self.failed_cells = []
    def col_calc(self,col_constraints):
        self.col_bounds = []
        if(col_constraints):
            for x1,x2 in col_constraints:self.col_bounds.append((x1,x2))
        for row in self.cell_bounding_boxes:
            for x,y,w,h in row:
                if(not contains(self.largest_rect,(x,y,w,h))):continue
                foundX = False
                for x1,x2 in self.col_bounds:
                    if(x1-5 <= x and x+w <= x2+5):
                        foundX = True
                        break
                if not foundX: self.col_bounds.append((x,x+w))
        self.col_bounds.sort(key = lambda x:x[1])
        return
    def row_calc(self,row_constraints=[],use_textbox=False):
        self.row_bounds = [(y1,y2) for y1,y2 in row_constraints]
        if not use_textbox:
            for row in self.cell_bounding_boxes:
                for x,y,w,h in row:
                    if(h > self.median * 4):continue
                    if(not contains(self.largest_rect,(x,y,w,h))):continue
                    foundY = False
                    for y1,y2 in self.row_bounds:
                        if(y1-10 <= y and y+h <= y2+10):
                            foundY = True
                            break
                    if not foundY: self.row_bounds.append((y,y+h))
            if(len(self.row_bounds) < 4):
                print("AUTO, no row seperator")
                use_textbox = True
        if use_textbox:
            self.row_bounds = [(y1,y2) for y1,y2 in row_constraints]
            for (x,y),(_,_),(x2,y2),(_,_) in self.text_bounding_boxes:
                w,h = x2-x,y2-y
                foundY = False
                for idx,(y1,y2) in enumerate(self.row_bounds):
                    if(y1-10 <= y and y+h <= y2+10):
                        foundY = True
                        self.row_bounds[idx] = (min(y1,y),max(y2,y+h))
                        break
                if not foundY: self.row_bounds.append((y,y+h))
        self.row_bounds.sort(key = lambda x:x[1])
        return
    def OCR(self):
        boxes = [[[] for i in range(len(self.col_bounds))] for j in range(len(self.row_bounds))]
        # print(self.median)
        # print(self.row_bounds)
        for (x1,y1),(_,_),(x2,y2),(_,_) in self.text_bounding_boxes:
            if(not contains(self.largest_rect,(x1,y1,x2-x1,y2-y1))):
                continue
            col_num,row_num = -1,-1
            for idx,(left,right) in enumerate(self.col_bounds):
                if(left-10 <= x1 and x2 <= right+10):
                    col_num = idx
                    self.col_bounds[idx] = (min(x1,left),max(x2,right))
                    break
            for idy,(top,bot) in enumerate(self.row_bounds):
                if(top-self.median//4 <= y1 and y2 <= bot+self.median//4):
                    row_num = idy
                    self.row_bounds[idy] = (min(y1,top),max(y2,bot))
                    break
            if(col_num >= 0 and row_num >= 0):
                boxes[row_num][col_num].append((x1,y1,x2,y2))
            """
            else:
                x1,x2,y1,y2 = int(x1),int(x2),int(y1),int(y2)
                if(col_num < 0 and row_num < 0):
                    print(f"SKIPPED, no matching column and row {(x1,y1,x2,y2)} , {pytesseract.image_to_string(Image.fromarray(self.image[y1:y2,x1:x2]),config='--psm 3')}")
                elif(col_num < 0):
                    print(f"SKIPPED, no matching column {(x1,y1,x2,y2)} , {pytesseract.image_to_string(Image.fromarray(self.image[y1:y2,x1:x2]),config='--psm 3')}")
                elif(row_num < 0):
                    print(f"SKIPPED, no matching row {(x1,y1,x2,y2)} , {pytesseract.image_to_string(Image.fromarray(self.image[y1:y2,x1:x2]),config='--psm 3')}")
            """
        cell_text = [["" for i in range(len(self.col_bounds))] for j in range(len(self.row_bounds))]
        self.failed_cells = []
        for idy,boxrow in enumerate(boxes):
            for idx,cell in enumerate(boxrow):
                minx1,miny1,maxx2,maxy2 = None,None,None,None
                for x1,y1,x2,y2 in cell:
                    if(minx1):
                        minx1,miny1,maxx2,maxy2 = min(x1,minx1),min(y1,miny1),max(x2,maxx2),max(y2,maxy2)
                    else:
                        minx1,miny1,maxx2,maxy2 = x1,y1,x2,y2
                if(minx1):
                    minx1,miny1,maxx2,maxy2 = int(minx1),int(miny1),int(maxx2),int(maxy2)
                    cell_text[idy][idx] = pytesseract.image_to_string(Image.fromarray(self.image[miny1:maxy2,minx1:maxx2]),config='--psm 6').replace("\n", "").replace("\f", "")
                else:
                    self.failed_cells.append((idx,idy))
                    # if(idy < len(self.cell_bounding_boxes) and idx < len(self.cell_bounding_boxes[0])):
                        # x,y,w,h = self.cell_bounding_boxes[idy][idx]
                        # minx1,miny1,maxx2,maxy2 = x,y,x+w,y+h
                        # cell_text[idy][idx] = pytesseract.image_to_string(Image.fromarray(self.image[miny1:maxy2,minx1:maxx2]),config='--psm 6')
        self.cell_text = cell_text
    def output_data(self):
        # print(self.cell_text)
        header_row = 0
        for idy,row in enumerate(self.cell_text):
            row_len = sum([len(s) for s in row])
            if(row_len > 2):
                header_row = idy
                break
        header = self.cell_text[header_row]
        data = self.cell_text[header_row+1:]
        out = []
        if(not self.no_sep_case):
            for idx,idy in self.failed_cells:
                if(idy < header_row):continue
                x,y,w,h = self.cell_bounding_boxes[idy][idx]
                minx1,miny1,maxx2,maxy2 = x,y,x+w,y+h
                self.cell_text[idy][idx] = pytesseract.image_to_string(Image.fromarray(self.image[miny1:maxy2,minx1:maxx2]),config='--psm 6').replace("\n", "").replace("\f", "")
        for row in data:
            row_dict = {}
            for head,cell in zip(header,row):
                row_dict[head] = cell
            out.append(row_dict)
        return self.cell_text[header_row:]
    def no_sep_method(self,rect = None,col_contraints = [],row_constrains = []):
        if rect is None:return
        self.largest_rect = rect
        filtered_textbox = []
        self.row_bounds = [(y1,y2) for y1,y2 in row_constrains]
        self.col_bounds = [(x1,x2) for x1,x2 in col_contraints]
        for (x1,y1),(_,_),(x2,y2),(_,_) in self.text_bounding_boxes:
            x,y,w,h = x1,y1,x2-x1,y2-y1
            if contains(rect,(x,y,w,h)):filtered_textbox.append((x,y,w,h))
        for x,y,w,h in filtered_textbox:
            foundX,foundY = False,False
            for idx,(y1,y2) in enumerate(self.row_bounds):
                if(y1-10 < y+h and y < y2+10):
                    foundY = True
                    self.row_bounds[idx] = (min(y1,y),max(y2,y+h))
                    break
            for idx,(x1,x2) in enumerate(self.col_bounds):
                if(x1-10 < x+w and x < x2+10):
                    foundX = True
                    self.col_bounds[idx] = (min(x1,x),max(x2,x+w))
                    break
            if not foundY:self.row_bounds.append((y,y+h))
            if not foundX:self.col_bounds.append((x,x+w))
        # print(f"Cols:{self.col_bounds}, \n\nRows:{self.row_bounds}")
        self.row_bounds.sort(key = lambda x:x[1])
        self.col_bounds.sort(key = lambda x:x[1])
        heights = [y2-y1 for y1,y2 in self.row_bounds]
        heights.sort()
        self.median = heights[len(heights)//2]
        self.failed_cells = []
        self.no_sep_case = True
        return
def contains(big,small):
    bx,by,bw,bh = big
    sx,sy,sw,sh = small
    bx2,by2 = bx+bw,by+bh
    sx2,sy2 = sx+sw,sy+sh
    return (sx >= bx-5 and sx2 <= bx2+5 and sy >= by-5 and sy2 <= by2+5)

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

def extract_cell_images_from_table(image,scale=5):
    BLUR_KERNEL_SIZE = (17, 17)
    STD_DEV_X_DIRECTION = 0
    STD_DEV_Y_DIRECTION = 0
    blurred = cv2.GaussianBlur(image, BLUR_KERNEL_SIZE, STD_DEV_X_DIRECTION, STD_DEV_Y_DIRECTION)
    MAX_COLOR_VAL = 255
    BLOCK_SIZE = 15
    SUBTRACT_FROM_MEAN = -2
    
    img_bin = cv2.adaptiveThreshold(
        ~blurred,
        MAX_COLOR_VAL,
        cv2.ADAPTIVE_THRESH_MEAN_C,
        cv2.THRESH_BINARY,
        BLOCK_SIZE,
        SUBTRACT_FROM_MEAN,
    )
    vertical = horizontal = img_bin.copy()
    # SCALE = 5
    SCALE = scale
    # cv2.imshow('mask',img_bin)
    # cv2.waitKey(0)
    image_width, image_height = horizontal.shape
    horizontal_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (int(image_width / SCALE), 1))
    horizontally_opened = cv2.morphologyEx(img_bin, cv2.MORPH_OPEN, horizontal_kernel)
    vertical_kernel = cv2.getStructuringElement(cv2.MORPH_RECT, (1, int(image_height / SCALE)))
    vertically_opened = cv2.morphologyEx(img_bin, cv2.MORPH_OPEN, vertical_kernel)
    horizontally_dilated = cv2.dilate(horizontally_opened, cv2.getStructuringElement(cv2.MORPH_RECT, (40, 1)))
    vertically_dilated = cv2.dilate(vertically_opened, cv2.getStructuringElement(cv2.MORPH_RECT, (1, 60)))
    
    mask = horizontally_dilated + vertically_dilated
    contours, heirarchy = cv2.findContours(
        mask, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE,
    )
    
    perimeter_lengths = [cv2.arcLength(c, True) for c in contours]
    epsilons = [0.05 * p for p in perimeter_lengths]
    approx_polys = [cv2.approxPolyDP(c, e, True) for c, e in zip(contours, epsilons)]
    
    # Filter out contours that aren't rectangular. Those that aren't rectangular
    # are probably noise.
    approx_rects = [p for p in approx_polys if len(p) == 4]
    bounding_rects = [cv2.boundingRect(a) for a in approx_polys]
    
    # Filter out rectangles that are too narrow or too short.
    MIN_RECT_WIDTH = 40
    MIN_RECT_HEIGHT = 10
    bounding_rects = [
        r for r in bounding_rects if MIN_RECT_WIDTH < r[2] and MIN_RECT_HEIGHT < r[3]
    ]
    
    # The largest bounding rectangle is assumed to be the entire table.
    # Remove it from the list. We don't want to accidentally try to OCR
    # the entire table.
    if(len(bounding_rects) == 0):return None,None
    largest_rect = max(bounding_rects, key=lambda r: r[2] * r[3])
    bounding_rects = [b for b in bounding_rects if b is not largest_rect]
    
    cells = [c for c in bounding_rects]
    def cell_in_same_row(c1, c2):
        c1_center = c1[1] + c1[3] - c1[3] / 2
        c2_bottom = c2[1] + c2[3]
        c2_top = c2[1]
        return c2_top < c1_center < c2_bottom
    
    orig_cells = [c for c in cells]
    rows = []
    while cells:
        first = cells[0]
        rest = cells[1:]
        cells_in_same_row = sorted(
            [
                c for c in rest
                if cell_in_same_row(c, first)
            ],
            key=lambda c: c[0]
        )
    
        row_cells = sorted([first] + cells_in_same_row, key=lambda c: c[0])
        rows.append(row_cells)
        cells = [
            c for c in rest
            if not cell_in_same_row(c, first)
        ]
    
    # Sort rows by average height of their center.
    def avg_height_of_center(row):
        centers = [y + h - h / 2 for x, y, w, h in row]
        return sum(centers) / len(centers)

    rows.sort(key=avg_height_of_center)
    return rows,largest_rect

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
    print("TB2")
    polys3 = []
    for poly2 in polys2:
        poly3 = [[poly2[0],poly2[1]],[poly2[2],poly2[1]],[poly2[2],poly2[3]],[poly2[0],poly2[3]]]
        polys3.append(np.array(poly3))
    print("TB3")
    return boxes, polys3, ret_score_text

def main(f):
    device = torch.device("cuda:0" if torch.cuda.is_available() else "cpu")
    model_textbox = CRAFT()
    model_textbox.load_state_dict(copyStateDict(torch.load("craft_mlt_25k.pth", map_location=torch.device(device))))
    if torch.cuda.is_available() :
        model_textbox = model_textbox.cuda()
    model_textbox = torch.nn.DataParallel(model_textbox)
    cudnn.benchmark = False
    model_textbox.eval()
    op = orderParser(model_textbox,f)
    # op.output_data()
    x1,y1,x2,y2 = 86,1142,2296,3032
    x,y,w,h = x1,y1,x2-x1,y2-y1
    op.no_sep_method(rect = (x,y,w,h))
    op.OCR()
    print(op.output_data())
    return
if __name__ == '__main__':
    paths = main(sys.argv[1])