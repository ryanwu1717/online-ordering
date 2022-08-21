import extract_msg
import os
import shutil
import uuid
import json

savepath = "../uploads/"

def msgParse(files):
    outs = []
    for fname in files:
        msg = extract_msg.openMsg(savepath + fname)
        msg_header = msg.header
        msg_body = msg.body
        lookup = {}
        attachments = msg.attachments
        for attachment in attachments:
            original_name, file_extension = os.path.splitext(attachment.longFilename)
            filename = str(uuid.uuid4())
            while(os.path.exists(savepath+filename+file_extension)):
                filename = str(uuid.uuid4())
            attachment.save(customFilename = filename+file_extension)
            shutil.move(filename+file_extension, savepath+filename+file_extension)
            lookup[original_name+file_extension] = filename+file_extension
        json_obj = {'Filename':fname,'Header':str(msg_header),'Body':str(msg_body),'Attachments':lookup}
        outs.append(json_obj)
    return outs

if __name__ == '__main__':
    print("Test")
    #print(msgParse(["2010110_FW_Quotation.msg"]))