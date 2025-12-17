import sys
import pymongo
from pymongo import UpdateOne
import subprocess
import json
import random
from bson import ObjectId
from datetime import datetime, timezone
import calendar

mongoDb = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017,mongob.pecosys.com:27017,mongoc.pecosys.com:27017/?authSource=admin&readPreference=secondaryPreferred&replicaSet=rs1"

def getClientByWlId(wlid_prm):
    global mongoDb
    # MongoDB connection
    #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
    client = pymongo.MongoClient(mongoDb)
    db_hursPortal = client['hursPortal'] # QA_Samples
    ClientsCol = db_hursPortal['client_details']
    clnt_query = {
            "wlId": int(wlid_prm)
        }
    clnt_projection = {
        "clientAbr": 1,
        "_id": 0
    }

    clnt_result = ClientsCol.find(clnt_query, clnt_projection)

    client_abr = clnt_result[0]['clientAbr']

    return client_abr

def getRuntimeVals(repId, wlId):
    qry = f"select DATE_FORMAT(FROM_UNIXTIME(runtime), '%Y%m%d') runtime from hurs_runned_reports where id = {repId} limit 1;"
    python_command = ["python3.9", "/var/www/html/hursPortal/pythonScripts/mysql_query.py", str(wlId), qry]
    
    try:
        ini_out = subprocess.check_output(python_command, stderr=subprocess.STDOUT)
        sndDtls = json.loads(ini_out)
        
        res = 0
        for r in sndDtls:
            res = r['runtime']
        
        return res
    except subprocess.CalledProcessError as e:
        print(f"Command failed with error: {e.output.decode()}")
        return None

def getRunType(date):
    # Convert the date string to a DateTime object
    dateObj = datetime.strptime(date, '%Y%m%d')
    
    # Get the day of the month
    day = int(dateObj.strftime('%d'))
    
    # Get the total number of days in the month
    daysInMonth = calendar.monthrange(dateObj.year, dateObj.month)[1]
    
    # Determine if the date is closer to the start or end of the month
    if day <= 5 or day >= daysInMonth - 9:
        return "Billing Run"
    else:
        return "AMI Run"



def main():
    if len(sys.argv) != 7:
        print("Usage: python3.9 generateSamples.py <frmRepId> <toRepId> <sndT> <wlId> <usrNme> <reason>")
        return

    frRpId = int(sys.argv[1])
    toRpId = int(sys.argv[2])
    sndTyp = sys.argv[3]
    wlIds = int(sys.argv[4])
    usr = sys.argv[5]
    rs = sys.argv[6]
    tpVal = 0
    im_samples_query = None


    # MongoDB connection
    client = pymongo.MongoClient(mongoDb)
    db_hers = client['hers'] # imaging
    db_hursPortal = client['hursPortal'] # QA_Samples

    imagingCol = db_hers['imaging']
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
        }
    else:
        im_samples_query = {
            "wlId": int(wlIds)
        }
    # get unique reportId(S) in imaging
    
    im_samples_projection = {
        "reportId": 1,
        "_id": 0
    }
    im_samples_result = imagingCol.find(im_samples_query, im_samples_projection)
    unique_rep_ids = {doc["reportId"] for doc in im_samples_result}
    #print("Unique img ids:", list(unique_rep_ids))
    '''unique_rep_ids = set()
    for doc in im_samples_result:
        unique_rep_ids.add(doc["reportId"])
        if len(unique_rep_ids) == 10:
            break'''


    # Query QA_Samples to get unique custIds and reportIds
    # Define the query and projection
    qa_samples_query = {
        "wlId": int(wlIds),
        "qaRemoved" : "N"
    }
    qa_samples_projection = {
       # "custId": 1,
        "reportId": 1,
        "_id": 0
    }

    # Execute the query
    qa_samples_result = QASampleCol.find(qa_samples_query, qa_samples_projection)

    # Convert the cursor to a list to print and iterate multiple times
    qa_samples_list = list(qa_samples_result)

    # Print the raw query result
    #print("Query Result:", qa_samples_list)

    # Extract unique custIds and reportIds
   # unique_cust_ids = {doc["custId"] for doc in qa_samples_list}
    unique_rpt_ids = {doc["reportId"] for doc in qa_samples_list}
    #print("Unique reportIds:", list(unique_rpt_ids))

    message += "{"
    ctr = 1
    for repId in unique_rep_ids:
        #print(repId)

        wlID = wlIds

        # Print the unique custIds and reportIds
        #print("Unique custIds:", list(unique_cust_ids))
        
        # "reportId": { "$nin": list(unique_rpt_ids) }
        if repId in unique_rpt_ids:
            message += '"' + str(repId) + '" : {'
            message += '"Status": "Sample Already generated"'
            message += f', "Email_samples_size": 0'
            message += f', "Paper_samples_size": 0'
            message += f', "GenStats":"N/A"'
            message += "}"

            if len(unique_rep_ids) > 1 and ctr < len(unique_rep_ids):
                message += ","
                ctr += 1
            
            continue
        else:
            message += '"' + str(repId) + '" : {'

        age= [
                {
                    "$match": {
                    "reportId": repId,
                    "reportType": "email",
                    "wlId": wlID,
                    "exclusions":{"$exists": False}
                    }
                },
                {
                    "$addFields": {
                    "reportKey": {
                        "$concat": [
                        { "$toString": "$reportId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$project": {
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "custId": 1,
                    "printReport": 1,
                    "imaging_id": "$_id",
                    "reportKey": 1,
                    "_id": 0
                    }
                },
                {
                    "$lookup": {
                    "from": "report_runs",
                    "let": { "reportKey": "$reportKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "reportKey": {
                            "$concat": [
                                { "$toString": "$reportId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$reportKey", "$$reportKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "reportId": 1,
                            "wlId": 1
                        }
                        }
                    ],
                    "as": "reportRuns"
                    }
                },
                { "$unwind": "$reportRuns" },
                {
                    "$addFields": {
                    "groupKey": {
                        "$concat": [
                        { "$toString": "$reportRuns.cohortId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$lookup": {
                    "from": "cohorts",
                    "let": { "groupKey": "$groupKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "groupKey": {
                            "$concat": [
                                { "$toString": "$cohortId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$groupKey", "$$groupKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "cohort_name": "$name",
                            "groupName": 1
                        }
                        }
                    ],
                    "as": "groupDetails"
                    }
                },
                { "$unwind": "$groupDetails" },
                {
                    "$project": {
                    "imaging_id": 1,
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "cohortId": "$reportRuns.cohortId",
                    "custId": 1,
                    "printReport": 1,
                    "groupName": "$groupDetails.groupName",
                    "cohort_name": "$groupDetails.cohort_name"
                    }
                },
                {
                    "$sample": { "size": 100 }
                }
                ]
                

        agp= [
                {
                    "$match": {
                    "reportId": repId,
                    "reportType": "paper",
                    "wlId": wlID,
                    "exclusions":{"$exists": False}
                    }
                },
                {
                    "$addFields": {
                    "reportKey": {
                        "$concat": [
                        { "$toString": "$reportId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$project": {
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "custId": 1,
                    "printReport": 1,
                    "imaging_id": "$_id",
                    "reportKey": 1,
                    "_id": 0
                    }
                },
                {
                    "$lookup": {
                    "from": "report_runs",
                    "let": { "reportKey": "$reportKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "reportKey": {
                            "$concat": [
                                { "$toString": "$reportId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$reportKey", "$$reportKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "reportId": 1,
                            "wlId": 1
                        }
                        }
                    ],
                    "as": "reportRuns"
                    }
                },
                { "$unwind": "$reportRuns" },
                {
                    "$addFields": {
                    "groupKey": {
                        "$concat": [
                        { "$toString": "$reportRuns.cohortId" },
                        "_",
                        { "$toString": "$wlId" }
                        ]
                    }
                    }
                },
                {
                    "$lookup": {
                    "from": "cohorts",
                    "let": { "groupKey": "$groupKey" },
                    "pipeline": [
                        {
                        "$addFields": {
                            "groupKey": {
                            "$concat": [
                                { "$toString": "$cohortId" },
                                "_",
                                { "$toString": "$wlId" }
                            ]
                            }
                        }
                        },
                        {
                        "$match": {
                            "$expr": { "$eq": ["$groupKey", "$$groupKey"] }
                        }
                        },
                        {
                        "$project": {
                            "cohortId": 1,
                            "cohort_name": "$name",
                            "groupName": 1
                        }
                        }
                    ],
                    "as": "groupDetails"
                    }
                },
                { "$unwind": "$groupDetails" },
                {
                    "$project": {
                    "imaging_id": 1,
                    "reportId": 1,
                    "wlId": 1,
                    "reportType": 1,
                    "productType": 1,
                    "cohortId": "$reportRuns.cohortId",
                    "custId": 1,
                    "printReport": 1,
                    "groupName": "$groupDetails.groupName",
                    "cohort_name": "$groupDetails.cohort_name"
                    }
                },
                {
                    "$sample": { "size": 100 }
                }
                ]

        res_email_image = list(imagingCol.aggregate(age))
        res_paper_image = list(imagingCol.aggregate(agp))

        resImgEmailSze = len(res_email_image) if res_email_image else 0
        resImgPaperSze = len(res_paper_image) if res_paper_image else 0

        #print(f"Email samples size: {resImgEmailSze}")
        #print(f"Paper samples size: {resImgPaperSze}")

        randm_EmlRecs = []
        randm_PprRecs = []
        final_samples = []

        if resImgEmailSze >= 25 and resImgPaperSze >= 25:
            message += '"Status": "Both email and paper samples avialable"'
            max_smpls_ppr = min(25, len(res_paper_image), len(res_email_image))
            randm_PprRecs = random.sample(res_paper_image, max_smpls_ppr)
            
            max_smpls_eml = min(25, len(res_email_image), len(res_email_image))
            randm_EmlRecs = random.sample(res_email_image, max_smpls_eml)
        elif resImgEmailSze >= 25 and resImgPaperSze < 25:
            message += '"Status": "Email samples avialable paper samples not yet available"'
            max_smpls_ppr = min(25, len(res_paper_image), len(res_email_image))
            randm_PprRecs = random.sample(res_paper_image, max_smpls_ppr)
          
            emlSze = 50 - len(randm_PprRecs)
            
            max_smpls_eml = min(emlSze, len(res_email_image), len(res_email_image))
            randm_EmlRecs = random.sample(res_email_image, max_smpls_eml)
            
        elif resImgEmailSze < 25 and resImgPaperSze >= 25:
            message += '"Status": "Paper samples avialable email samples not yet available"'
            max_smpls_eml = min(25, len(res_email_image), len(res_email_image))
            randm_EmlRecs = random.sample(res_email_image, max_smpls_eml)
            
            pprSze = 50 - len(randm_EmlRecs)
            
            max_smpls_ppr = min(pprSze, len(res_paper_image), len(res_email_image))
            randm_PprRecs = random.sample(res_paper_image, max_smpls_ppr)
        elif resImgEmailSze < 25 and resImgPaperSze < 25:
            message += '"Status": "Sufficent samples for email and paper not avialable no generation possible"'
        
        rndImgEmailSze = len(randm_EmlRecs) if randm_EmlRecs else 0
        rndImgPaperSze = len(randm_PprRecs) if randm_PprRecs else 0

        message += f', "Email_samples_size": {rndImgEmailSze}'
        message += f', "Paper_samples_size": {rndImgPaperSze}'
        

        # move loop forwards if no data to generate samples
        if resImgEmailSze < 25 and resImgPaperSze < 25:
            continue

        # generate a final sample list
        final_samples = randm_EmlRecs + randm_PprRecs

        # Insert multiple documents
        #QASampleCol.insert_many(final_samples)

        # Create a list of UpdateOne operations for upsert
        operations = []
        srn = 0
        dateTimeVal = datetime.now(timezone.utc)
        for sample in final_samples:
            srn += 1
            sample['sr'] = srn
            sample['client'] = getClientByWlId(sample["wlId"])
            sample['downFileNme'] = sample['client'] + '_' + str(sample['cohort_name']).replace(' ', '_') + '_' + sample['reportType'] + '_' + str(sample['wlId']) + '_' + str(sample['reportId']) + '_' + str(sample['custId']) + '.pdf'
            sample['runtime'] = getRuntimeVals(sample['reportId'],sample["wlId"])
            sample['runtype'] = getRunType(sample['runtime'])
            sample['passedQA'] = "N"
            sample['showToPms'] = "N"
            sample['qaRemoved'] = "N"
            sample['repVsPortal'] = None
            sample['qaReason'] = {
                                    "0": {
                                        "commenterType": "SYS",
                                        "madeBy": usr,
                                        "madeOn": dateTimeVal,
                                        "comment": ""
                                    }
                                }
            sample['wooFields'] = {
                                    "createdOn": dateTimeVal,
                                    "updatedOn": dateTimeVal,
                                    "createdBy": usr,
                                    "updatedBy": usr
                                }


            operation = UpdateOne(
                {"custId": sample["custId"], "reportId": sample["reportId"], "wlId": sample["wlId"]},  # Filter criteria
                {"$set": sample},  # Update operation
                upsert=True  # Perform an upsert
            )
            operations.append(operation)

        #print(list(final_samples))
        # Perform the bulk upsert
        result = QASampleCol.bulk_write(operations)

        message += f', "GenStats":"Samples Existing={result.matched_count}; Samples Saved={result.upserted_count}"'

        message += "}"

        if len(unique_rep_ids) > 1 and ctr < len(unique_rep_ids):
            message += ","
            ctr += 1
        
        '''print()
        print(f"lnt: {len(unique_rep_ids)}")
        print()
        print(f"ctr: {ctr}")'''
    
        
    message += "}"
        
    # Your logic to generate samples using frRpId, toRpId, and sndTyp
    #message = f"Python Received: From Rep ID: {frRpId}, To Rep ID: {toRpId}, Send Type: {sndTyp}, Wlid: {wlIds}"
    print(message)

    # IF ALL PARAMETERS RECIVED = 0 then we will run for all avialable cohorts in imaging minus all allready processed
    # IF Any parameter is given the data will be fetched accordingly
    # Once data recived fetch seperate for email and paper for each report id and then get 25 random samples from paper and email each
    # Combine the above to create the complete set of 50 sample per report id
    # if any set has missing data i.e less or no email data found to randomly get 35 samples we need to complete for remaining data from other group

if __name__ == "__main__":
    main()