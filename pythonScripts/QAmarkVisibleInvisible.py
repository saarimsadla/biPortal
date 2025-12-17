import sys
import pymongo
from datetime import datetime, timezone

def main():
    if len(sys.argv) != 7:
        print("Usage: python3.9 QAmarkVisibleInvisible.py <frmRepId> <toRepId> <sndT> <wlId> <usrNme> <reason>")
        return

    frRpId = int(sys.argv[1])
    toRpId = int(sys.argv[2])
    sndTyp = int(sys.argv[3])
    wlIds = int(sys.argv[4])
    usr = sys.argv[5]
    rs = sys.argv[6]
    im_samples_query = None

    #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
    mongoDb = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017,mongob.pecosys.com:27017,mongoc.pecosys.com:27017/?authSource=admin&readPreference=primary&replicaSet=rs1"
    client = pymongo.MongoClient(mongoDb)
    db_hursPortal = client['hursPortal']  # QA_Samples

    QASampleCol = db_hursPortal['QASamples']

    message = ""


    if frRpId > 0 or toRpId > 0:
        im_samples_query = {
            "wlId": int(wlIds),
            "reportId": frRpId,
            "sr" : sndTyp,
            "custId" : toRpId
            , "qaRemoved" : "N"
        }
    else:
        message += '{"Status" : "Please select from to Report Id to mark cannot mark for all"}'
        print(message)
        return

    samples = QASampleCol.find(im_samples_query)

    for sample in samples:
        if sample.get("showToPms") == "Y":
            QASampleCol.update_one({"_id": sample["_id"]}, {"$set": {"showToPms": "N", "wooFields.updatedOn": datetime.now(timezone.utc), "wooFields.updatedBy":usr}})
            message = '{"Status" : "Sample Now Hidden"}'
            print(message)
        elif sample.get("showToPms") == "N":
            QASampleCol.update_one({"_id": sample["_id"]}, {"$set": {"showToPms": "Y", "wooFields.updatedOn": datetime.now(timezone.utc), "wooFields.updatedBy":usr }})
            message = '{"Status" : "Sample Now Visible"}'
            print(message)

if __name__ == "__main__":
    main()