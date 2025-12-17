import sys
import pymongo
from datetime import datetime, timezone

def main():
    if len(sys.argv) != 7:
        print("Usage: python3.9 makQaPass.py <frmRepId> <toRepId> <sndT> <wlId> <usrNme> <reason>")
        return

    frRpId = int(sys.argv[1])
    toRpId = int(sys.argv[2])
    sndTyp = sys.argv[3]
    wlIds = int(sys.argv[4])
    usr = sys.argv[5]
    rsn = sys.argv[6]
    tpVal = 0
    im_samples_query = None

    #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
    mongoDb = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017,mongob.pecosys.com:27017,mongoc.pecosys.com:27017/?authSource=admin&readPreference=primary&replicaSet=rs1"
    client = pymongo.MongoClient(mongoDb)
    db_hursPortal = client['hursPortal'] # QA_Samples

    QASampleCol = db_hursPortal['QASamples']

    message = ""

    im_samples_query = {
        "wlId": int(wlIds)
        ,"reportId":{"$gte": frRpId, "$lte": toRpId}
        ,"qaRemoved" : "N"  

    }

    docs = QASampleCol.find(im_samples_query)
    
    rsns = ""
    if rsn is None or rsn == "":
        rsns = "Batch Bulk Reverted"
    else:
        rsns = rsn
   
    for doc in docs:
        qa_reason = doc.get("qaReason", {})
        max_key = max(map(int, qa_reason.keys()), default=-1)
        next_key = str(max_key + 1)

        # Add new entry
        qa_reason[next_key] = {
            "comment": rsns,
            "commenterType": sndTyp,
            "madeBy": usr,
            "madeOn": datetime.now(timezone.utc)
        }

        # Update the document
        QASampleCol.update_one(
            { "_id": doc["_id"] },
            {
            "$set": {
                    "qaReason": qa_reason,
                    "passedQA": "N", # or "F" or "N"
                    "wooFields.updatedOn": datetime.now(timezone.utc),
                    "wooFields.updatedBy": usr
                }
            }
        )


    message += '{"Status" : "Sample reverted to QA Queue Report Id: ' + str(frRpId) + ' And to Report Id: ' + str(toRpId) +'"}'
    print(message)


if __name__ == "__main__":
    main()