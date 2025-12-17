import sys
import pymongo
import json
from bson import ObjectId
'''
import logging
import os

log_file = 'mongoDbQueryLog.log'
if os.path.exists(log_file):
    os.remove(log_file)
 
logging.basicConfig(filename=log_file, level=logging.INFO, 
                    format='%(lineno)d:%(asctime)s:%(levelname)s:%(message)s')
'''
# python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py salesForce journey_details.bounce_details '{"wlId":46, "eventId":35, "transactionSr":2}' 'a' '[<your_aggregation_pipeline_here>]'

# Function to convert ObjectId to string
def convert_objectid(result):
    for resul in result:
        if isinstance(resul.get('_id'), ObjectId):
            resul['_id'] = str(resul['_id'])
        elif isinstance(resul.get('_id'), dict) and '$oid' in resul['_id']:
            resul['_id'] = resul['_id']['$oid']
    return result


def query_mongodb(envNMe,database_name, collectionName, func, query):
    try:
        # MongoDB connection
        #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
        mongoDb = ""
        if envNMe == 'prod':
            mongoDb = "mongodb://sasadla:sasadla123*@localhost:27017/?authMechanism=DEFAULT"
        else:
            mongoDb = "mongodb://sasadla:sasadla123*@localhost:27017/?authMechanism=DEFAULT"

        client = pymongo.MongoClient(mongoDb)
        db = client[database_name]
        if func == "countDocuments":
            collection = db[collectionName]
            result = collection.count_documents(query)  # returns an integer
            return {"count": result}

        elif func == "find":
            collection = db[collectionName]
            result = list(collection.find(query))
            res = convert_objectid(result)  # assuming this converts ObjectId to string
            return res

        else:
            return {"error": "Function definition not found."}

    except Exception as e:
        return {"error" : str(e)}
        #logging.error(f"error: {str(e)}")
 
if __name__ == "__main__":
    #envName=test&dbName=portal&colName=hers_reports&fnc=countDocuments&qry={"wlId":46,"custId":10508403}
    if len(sys.argv) != 6:
        print("Usage: python3.9 mongodb_query_V2.py <envName> <dbName> <colName> <fnc> <qry>")
        sys.exit(1)
    
    envNMe = sys.argv[1]
    database_name = sys.argv[2]
    collectionNme = sys.argv[3]
    func = sys.argv[4]
    
    query = json.loads(sys.argv[5])
    
    result = query_mongodb(envNMe,database_name, collectionNme, func, query)
    print(json.dumps(result,default=str))