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


def query_mongodb(database_name, journey_collection_name, bounce_collection_name, query, typ, ag):
    try:
        # MongoDB connection
        #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
        mongoDb = "mongodb://sasadla:sasadla123*@localhost:27017/?authMechanism=DEFAULT"
        client = pymongo.MongoClient(mongoDb)
        db = client[database_name]
        if typ == "q":
            # Simple query on bounce collection
            if "_QF" in journey_collection_name:
                # Split the string on the semicolon
                
                #query.replace("// Exclude the default _id field", "")

                # Add the closing brace back to each part and convert to dictionaries
                qry = query['qry']
                flds = query['fld']
                #logging.info(qry)
                #logging.info(flds)
                col_nme = journey_collection_name.split("_")[0]
                #logging.info(col_nme)
                collection = db[col_nme]
                result = list(collection.find(qry, flds))
                #print(result)
            else:
                collection = db[journey_collection_name]
                result = list(collection.find(query))
            # Convert ObjectId to string in the result
            res = convert_objectid(result)                                                           
            return res
        elif typ == "a":
            # Aggregation query
            wlId = int(query.get('wlId'))
            eventId = int(query.get('eventId'))
            transactionSr = query.get('transactionSr')
            eventType = 'Pre-Event' if int(transactionSr) == 1 else 'Post-Event' if int(transactionSr) == 2 else 'N/A'
            productType = 'HERS' if int(transactionSr) == 0 else 'BDR'
            # Fetch all relevant JourneyActivityObjectID from journey_details collection
            journey_details_collection = db[journey_collection_name]
            journey_query = {
                'wlId': wlId,
                'reportId': eventId,
                'eventType': eventType,
                'productType': productType
            }
            journey_docs = journey_details_collection.find(journey_query)
            journey_activity_ids = [doc['journeyActivityId'] for doc in journey_docs if 'journeyActivityId' in doc]
            if not journey_activity_ids:
                return {"error": "No matching JourneyActivityObjectID found in journey_details."}
            # Replace the JourneyActivityObjectID in the aggregation pipeline with $in operator
            for stage in ag:
                if "$match" in stage and "JourneyActivityObjectID" in stage["$match"]:
                    stage["$match"]["JourneyActivityObjectID"] = {"$in": journey_activity_ids}
            # Perform the aggregation on bounce_details collection
            bounce_details_collection = db[bounce_collection_name]
            result = list(bounce_details_collection.aggregate(ag))
 
            # Add additional fields to each document in the result
            for doc in result:
                doc['bdrEventId'] = eventId
                doc['transactionSr'] = transactionSr
                doc['eventType'] = eventType
                doc['wlId'] = wlId
            return result
        elif typ == "ag":
            # Aggregation query
            transactionSr = query.get('transactionSr')
            metric_details_collection = db[journey_collection_name]
            result = list(metric_details_collection.aggregate(ag))
            
            for doc in result:
                doc['transactionSr'] = transactionSr
            
            return result
        else:
            return {"error": "Invalid query type specified."}
    except Exception as e:
        return {"error" : str(e)}
        #logging.error(f"error: {str(e)}")
 
if __name__ == "__main__":
    if len(sys.argv) != 6:
        print("Usage: python mongodb_query.py <database_name> <journey_collection_name>.<bounce_collection_name> <initial_query_json> <typ> <ag>")
        sys.exit(1)
    database_name = sys.argv[1]
    # Split the collection name into journey and bounce collections
    collections = sys.argv[2].split('.')
    if len(collections) != 2:
        print("Error: <journey_collection_name>.<bounce_collection_name> should be specified correctly.")
        sys.exit(1)
    journey_collection_name = collections[0]
    bounce_collection_name = collections[1]
    #print(sys.argv[3])
    query = json.loads(sys.argv[3])
    typ = sys.argv[4]
    ag = json.loads(sys.argv[5])
    #print(ag)
    result = query_mongodb(database_name, journey_collection_name, bounce_collection_name, query, typ, ag)
    print(json.dumps(result,default=str))