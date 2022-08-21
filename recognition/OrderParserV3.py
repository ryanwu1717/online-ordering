import cv2
import pytesseract
import time

SCALE = 5
CUSTOM_OEM_PSM_CONFIG = r'--psm 12'

class order_parser():
    def __init__(self,file) -> None:
        self.image_route = file
        gray_scale = cv2.imread(self.image_route, cv2.IMREAD_GRAYSCALE)
        _,self.image_binarized = cv2.threshold(gray_scale,230,255,cv2.THRESH_BINARY)
        self.cell_bounding_boxes,self.largest_rect = self.extract_cell_images_from_table(self.image_binarized,SCALE)
    def extract_cell_images_from_table(self,image,scale=5):
    
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
    def fast_cell_recognition(self,threshold = -1):
        if not self.largest_rect:
            return [],[]
        image_data = pytesseract.image_to_data(self.image_binarized, config=CUSTOM_OEM_PSM_CONFIG, output_type= pytesseract.Output.DICT)
        high_conf_idx = []
        for idx,conf in enumerate(image_data['conf']):
            if float(conf) > threshold:
                high_conf_idx.append(idx)
        cell_datas = []
        for idx in high_conf_idx:
            x,y,w,h = image_data['left'][idx], image_data['top'][idx], image_data['width'][idx], image_data['height'][idx]
            if self.contains(self.largest_rect,[x,y,w,h]):
                cell_datas.append([[x,y,w,h],image_data['text'][idx]])
        cells = self.get_col_rows()
        failed = []
        for (x,y,w,h),text in cell_datas:
            found = False
            for j in range(len(cells)):
                for i in range(len(cells[j])):
                    if self.contains(self.cell_bounding_boxes[j][i],[x,y,w,h]):
                        found = True
                        cells[j][i].append(text)
                        # print(text)
                        break
                if found:
                    break
            if not found:
                failed.append([[x,y,w,h],text])
                # print(text)
        header_idx = None
        for idx,row in enumerate(cells):
            non_empty = 0
            for cell in row:
                if cell: 
                    non_empty += 1
            if non_empty > 2:
                header_idx = idx
                break
        if header_idx is None:
            return []
        header = ["".join(cell) for cell in cells[header_idx]]
        for row in cells:
            if len(row) > len(header):
                for i in range(len(header),len(row)):
                    header.append(" "*i)
        data = cells[header_idx+1:]
        out = []
        for row in data:
            row_dict ={}
            for idx,cell in enumerate(row):
                row_dict[header[idx]] = cell
            out.append(row_dict)
        return out
    def get_col_rows(self):
        table = []
        for row in self.cell_bounding_boxes:
            table.append([[] for _ in range(len(row))])
        return table
    def contains(self,big,small):
        bx,by,bw,bh = big
        sx,sy,sw,sh = small
        bx2,by2 = bx+bw,by+bh
        sx2,sy2 = sx+sw,sy+sh
        return (sx >= bx-5 and sx2 <= bx2+5 and sy >= by-5 and sy2 <= by2+5)
if __name__ == '__main__':
    image_path = '../uploads/DOC220304-20220304111155-2-page-001.jpg'
    # image_path = '../uploads/c1e96630-6737-4596-9545-21d9cc49e486.png'
    # image_path = '../uploads/19d2ea91-e354-482a-b455-76cf944b301e.jpg'
    OP = order_parser(image_path)
    # cv2.imshow("TMP",OP.image_binarized)
    # cv2.waitKey(0)
    ts = time.perf_counter()
    print(OP.fast_cell_recognition())
    print(time.perf_counter()-ts)
    """
    tmp = cv2.imread(image_path)
    # for (x,y,w,h),text in failed:
        # cv2.rectangle(tmp,(x,y),(x+w,y+h),(0,0,255),4)
        # cv2.putText(tmp,text,(x+w,y+h),cv2.FONT_HERSHEY_PLAIN ,2, (0, 0, 255), 2, cv2.LINE_AA)
    for j in range(len(cells)):
        for i in range(len(cells[j])):
            if cells[j][i]:
                x,y,w,h = OP.cell_bounding_boxes[j][i]
                cv2.rectangle(tmp,(x,y),(x+w,y+h),(255,0,0),8)
            else:
                x,y,w,h = OP.cell_bounding_boxes[j][i]
                cv2.rectangle(tmp,(x,y),(x+w,y+h),(0,0,255),8)
    cv2.imshow("TMP",tmp)
    cv2.waitKey(0)
    """