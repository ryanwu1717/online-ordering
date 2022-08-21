import time
from flask import Flask
from flask import jsonify
import torch
import imgproc
from torch.autograd import Variable
import craft_utils
import numpy as np
import merge
import merge2
from craft import CRAFT
import torch.backends.cudnn as cudnn
import argparse
import string
from utilss import CTCLabelConverter, AttnLabelConverter
from recmodel import Model as recModel
import cv2
from collections import OrderedDict

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

def getTextBox(net, image, refine_net=None):
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

    # print("\ninfer/postproc time : {:.3f}/{:.3f}".format(t0, t1))
    
    polys2 = []
    for poly in polys:
        poly2 = [poly[0][0],poly[0][1],poly[2][0],poly[2][1]]
        polys2.append(poly2)
    polys2 = np.array(polys2)
    polys2 = merge.non_max_suppression_fast(polys2,0.8)
    polys2 = merge2.non_max_suppression_fast(polys2,0,5)
    #polys2 = merge2.non_max_suppression_fast(polys2,0,3)
    #polys2 = merge2.non_max_suppression_fast(polys2,0,5)
    polys3 = []
    for poly2 in polys2:
        poly3 = [[poly2[0],poly2[1]],[poly2[2],poly2[1]],[poly2[2],poly2[3]],[poly2[0],poly2[3]]]
        polys3.append(np.array(poly3))
    return boxes, polys3, ret_score_text

def main():
    device = torch.device("cuda:0" if torch.cuda.is_available() else "cpu")
    model_textbox = CRAFT()
    model_textbox.load_state_dict(copyStateDict(torch.load("craft_mlt_25k.pth", map_location=torch.device(device))))
    if torch.cuda.is_available() :
        model_textbox = model_textbox.cuda()
    model_textbox = torch.nn.DataParallel(model_textbox)
    cudnn.benchmark = False
    model_textbox.eval()
    parser = argparse.ArgumentParser()

    parser.add_argument('--image_folder', default="CutTexts", help='path to image_folder which contains text images')
    parser.add_argument('--workers', type=int, help='number of data loading workers', default=4)
    parser.add_argument('--batch_size', type=int, default=400, help='input batch size')
    parser.add_argument('--saved_model', default="TPS-ResNet-BiLSTM-Attn.pth", help="path to saved_model to evaluation")
    """ Data processing """
    parser.add_argument('--batch_max_length', type=int, default=25, help='maximum-label-length')
    parser.add_argument('--imgH', type=int, default=32, help='the height of the input image')
    parser.add_argument('--imgW', type=int, default=100, help='the width of the input image')
    parser.add_argument('--rgb', action='store_true', help='use rgb input')
    parser.add_argument('--character', type=str, default='0123456789abcdefghijklmnopqrstuvwxyz', help='character label')
    parser.add_argument('--sensitive', action='store_true', help='for sensitive character mode')
    parser.add_argument('--PAD', action='store_true', help='whether to keep ratio then pad for image resize')
    """ Model Architecture """
    parser.add_argument('--Transformation', type=str, default="TPS", help='Transformation stage. None|TPS')
    parser.add_argument('--FeatureExtraction', type=str, default="ResNet", help='FeatureExtraction stage. VGG|RCNN|ResNet')
    parser.add_argument('--SequenceModeling', type=str, default="BiLSTM", help='SequenceModeling stage. None|BiLSTM')
    parser.add_argument('--Prediction', type=str, default="Attn", help='Prediction stage. CTC|Attn')
    parser.add_argument('--num_fiducial', type=int, default=20, help='number of fiducial points of TPS-STN')
    parser.add_argument('--input_channel', type=int, default=1, help='the number of input channel of Feature extractor')
    parser.add_argument('--output_channel', type=int, default=512,
                        help='the number of output channel of Feature extractor')
    parser.add_argument('--hidden_size', type=int, default=256, help='the size of the LSTM hidden state')

    opt = parser.parse_args()

    if opt.sensitive:
        opt.character = string.printable[:-6]  # same with ASTER setting (use 94 char).

    cudnn.benchmark = True
    cudnn.deterministic = True
    opt.num_gpu = torch.cuda.device_count()
    """ model configuration """
    if 'CTC' in opt.Prediction:
        converter = CTCLabelConverter(opt.character)
    else:
        converter = AttnLabelConverter(opt.character)
    opt.num_class = len(converter.character)
    device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')

    cudnn.deterministic = True
    model_textrec = recModel(opt)
    model_textrec = torch.nn.DataParallel(model_textrec).to(device)
    # load model
    model_textrec.load_state_dict(torch.load("TPS-ResNet-BiLSTM-Attn.pth", map_location=device))
    model_textrec.eval()

if __name__ == '__main__':
	main()