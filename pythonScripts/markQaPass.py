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

    if frRpId > toRpId:
        frRpId, toRpId = toRpId, frRpId

    if frRpId > 0 and toRpId <= 0:
        toRpId = frRpId

    if frRpId <= 0 and toRpId > 0:
        frRpId = toRpId

    if frRpId > 0 or toRpId > 0:
        im_samples_query = {
            "wlId": int(wlIds)
            ,"reportId": {"$gte": frRpId, "$lte": toRpId}
            ,"qaReason.0.comment": ""
            , "qaRemoved" : "N"
        }
    else:
        message += '{"Status" : "Please select from to Report Id to mark cannot mark for all"}'
        print(message)
        return
    
       # Fetch matching documents
    docs = QASampleCol.find(im_samples_query)

    stt = ""
    if sndTyp == "Y":
        stt = "Passed"
    elif sndTyp == "F":
        stt = "Failed"
    elif sndTyp == "N":
        stt = "In QA Queue"
    else:
        stt = "Unknown Status"

    rsns = ""
    if rsn is None or rsn == "":
        rsns = "Batch Bulk "+stt
    else:
        rsns = rsn

   
    for doc in docs:
        qa_reason = doc.get("qaReason", {})
        max_key = max(map(int, qa_reason.keys()), default=-1)
        next_key = str(max_key + 1)        
        last_comment = qa_reason.get(str(max_key), {}).get("commenterType", "").lower()
        commenterType = "SYS" if last_comment in ['sys'] else last_comment.upper()


        # Add new entry
        qa_reason[next_key] = {
            "comment": rsns,
            "commenterType": commenterType,
            "madeBy": usr,
            "madeOn": datetime.now(timezone.utc)
        }

        # Update the document
        QASampleCol.update_one(
            { "_id": doc["_id"] },
            {
            "$set": {
                    "qaReason": qa_reason,
                    "passedQA": "Y", # or "F" or "N"
                    "wooFields.updatedOn": datetime.now(timezone.utc),
                    "wooFields.updatedBy": usr
                }
            }
        )

    message += '{"Status" : "Marked QA '+stt+' for Report Id(s) from: ' + str(frRpId) + ' and to: ' + str(toRpId) +'"}'
    print(message)


if __name__ == "__main__":
    main()