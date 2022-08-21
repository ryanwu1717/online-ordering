from thefuzz import fuzz
import numpy as np
import TextBoxService
from craft import CRAFT
import torch
import torch.backends.cudnn as cudnn
import imgproc
import cv2
import pytesseract
import os
from time import perf_counter
from collections import defaultdict

coatingKeys = ['CrN','CRN', 'DLC', 'CVD', 'TiN','TIN', 'TICN', 'TiAlN','TIALN', 'AlCrN','ALCRN', 'Plating', 'Nitriding', 'Black Oxide', 'Without Coating', 'Flame Hardening']
materialKeys_single = ['D2', 'M2', 'M4', 'M7', 'S7', 'V4', 'G10', 'G15', 'G20', 'G30', 'G40', 'G50', 'G55', 'H13', 'M35', 'M42', 'T15', 
'4140', '4340', '8620', 'K340', 'K890', 'S45C', 'S390', 'SKH9', 'SUJ2', 'W360', 
'ASP23', 'ASP30', 'ASP60', 'Brass', 'HAP10', 'SKD11', 'SKD61', 'SKH51', 'SKH55', 'SKH59', 
'1.2343', '1.2344', '1.2365', '1.2367', '1.2379', '1.2842', '1.3243', '1.3247', '1.3343', 
'AS DWG', 'CPM-3V', 'CPM-M4', 'MiL-60', 'SCM415', 'SCM435', 'SCM440', 'SUS303', 'SUS304', 'SUS316', 'CPM-10V', 
'Carbide', 'MiL-60R', 'MiL-60S', 'MiL-TIP', 'SNCM220', 'STELITE', 'Aluminum', 'SUS420J1', 'SUS420J2', 'red copper', 
'C3604', 'M42-MiL-60S', 'Aluminum bar', 'C1100', 'C83600', 'C90700', 'C93210', 'C95400', 'C95500', 'C95800', 'C95810', 
'CuCrZr', 'SAE841', 'CAC502C', 'Solide', 'C5191', 'CAC406C', 'customer provided', 
'oxygen-free', 'SAE64','C93700', 'C17200', 'CuSn6','C51900', 'SAE660', 'C93200','M-2','M-4']
class Keywordfinder:
    def __init__(self) -> None:
        self.materialKeys_single = materialKeys_single
        self.coatingKeys = coatingKeys
        self.custom_config = r'--oem 3 --psm 7'
        self.model_textbox = CRAFT()
        device = torch.device("cuda:0" if torch.cuda.is_available() else "cpu")
        self.model_textbox.load_state_dict(TextBoxService.copyStateDict(torch.load("craft_mlt_25k.pth", map_location=torch.device(device))))
        if torch.cuda.is_available() :
            self.model_textbox = self.model_textbox.cuda()
        cudnn.benchmark = False
        self.model_textbox.eval()
    def get_material_coating(self,file_name):
        best_mat,best_rat = "",0
        best_coat,best_rat_coat = "",0
        with torch.no_grad():
            image = imgproc.loadImage(file_name)
            _,boxes,_ = TextBoxService.getTextBox(self.model_textbox,image)
            original_image = cv2.imread(file_name)
            for TL,TR,BR,BL in boxes:
                (y1,x1),(y2,x2) = TL,BR
                s = pytesseract.image_to_string(original_image[x1:x2,y1:y2,:],config=self.custom_config).strip()
                for mat in self.materialKeys_single:
                    if len(s) > len(mat)+2 and len(mat) <= 3:
                        rat = fuzz.partial_ratio(s,mat)
                    else:
                        rat = fuzz.ratio(s,mat)
                    if rat > 60 and rat > best_rat:
                        best_rat = rat
                        best_mat = mat
                for ss in s.split(' '):
                    for sss in ss.split("-"):
                        for coat in self.coatingKeys:
                            rat = fuzz.ratio(sss,coat)
                            if rat > 60 and rat > best_rat_coat:
                                best_rat_coat = rat
                                best_coat = coat
        return best_mat,best_coat

if __name__ == "__main__":
    custom_config = r'--oem 3 --psm 7'
    model_textbox = CRAFT()
    device = torch.device("cuda:0" if torch.cuda.is_available() else "cpu")
    model_textbox.load_state_dict(TextBoxService.copyStateDict(torch.load("craft_mlt_25k.pth", map_location=torch.device(device))))
    if torch.cuda.is_available() :
        model_textbox = model_textbox.cuda()
    # model_textbox = torch.nn.DataParallel(model_textbox)
    cudnn.benchmark = False
    model_textbox.eval()
    total = 0
    for root, dirs, files in os.walk('uploads/M2_M4/'):
        for name in files:
            t0 = perf_counter()
            print(name)
            passed = False
            with torch.no_grad():
                image = imgproc.loadImage(root+name)
                _,boxes,_ = TextBoxService.getTextBox(model_textbox,image)
                original_image = cv2.imread(root+name)
                best_mat,best_rat,best_s = "",0,""
                best_coat,best_rat_coat,best_s_coat = "",0,""
                poss = defaultdict(int)
                for TL,TR,BR,BL in boxes:
                    (y1,x1),(y2,x2) = TL,BR
                    s = pytesseract.image_to_string(original_image[x1:x2,y1:y2,:],config=custom_config).strip()
                    for mat in materialKeys_single:
                        if len(s) > len(mat)+2 and len(mat) <= 3:
                            rat = fuzz.partial_ratio(s,mat)
                        else:
                            rat = fuzz.ratio(s,mat)
                        if rat > 60 and rat > best_rat:
                            best_rat = rat
                            best_mat = mat
                            best_s = s
                    for ss in s.split(' '):
                        for sss in ss.split("-"):
                            for coat in coatingKeys:
                                rat = fuzz.ratio(sss,coat)
                                if rat > 60 and rat > best_rat_coat:
                                    best_rat_coat = rat
                                    best_coat = coat
                                    best_s_coat = sss
                t = perf_counter()-t0
                print(t,best_mat,best_s,best_coat,best_s_coat)
                total += t
        print(f"Files:{len(files)} Total time:{total}")