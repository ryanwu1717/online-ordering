from PyPDF2 import PdfFileWriter, PdfFileReader
from pdf2image import convert_from_path
import os
import shutil
import uuid
import json

savepath = "../uploads/"

def split(files):
    out = {}
    for fname in files:
        print(fname)
        pdf = PdfFileReader(savepath+fname)
        splitnames = []
        original_name, file_extension = os.path.splitext(fname)
        for page in range(pdf.getNumPages()):
            filename = str(uuid.uuid4())
            while(os.path.exists(savepath+filename+file_extension)):
                filename = str(uuid.uuid4())
            splitnames.append(filename+file_extension)
            pdf_writer = PdfFileWriter()
            pdf_writer.addPage(pdf.getPage(page))
            output_filename = savepath + filename + file_extension
            with open(output_filename, 'wb') as outfile:
                pdf_writer.write(outfile)
        out[fname] = splitnames
    return out
def split_jpg(files):
    out = {}
    for fname in files:
        pages = convert_from_path(savepath+fname, 500)
        splitnames = []
        for page in pages:
            filename = str(uuid.uuid4())
            while(os.path.exists(savepath+filename+'.jpg')):
                filename = str(uuid.uuid4())
            splitnames.append(filename+'.jpg')
            page.save(savepath+filename+'.jpg', 'JPEG')
        out[fname] = splitnames
    return out
if __name__ == '__main__':
    print(split(["3b75b0ca-08bc-437b-8582-8d6c08fc543e.pdf","6a8a87c2-9a16-4b16-b3cb-acd2e078b9db.pdf"]))