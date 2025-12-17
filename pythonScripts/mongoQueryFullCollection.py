import sys
import pymongo
import json
from bson import json_util
 
# python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py salesForce journey_details.bounce_details '{"wlId":46, "eventId":35, "transactionSr":2}' 'a' '[<your_aggregation_pipeline_here>]'
 
def query_mongodb(database_name,collection_name):
    try:
        # MongoDB connection
        #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
        mongoDb = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017,mongob.pecosys.com:27017,mongoc.pecosys.com:27017/?authSource=admin&readPreference=secondaryPreferred&replicaSet=rs1"
        #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.planetecosystems.com:27017/"
        client = pymongo.MongoClient(mongoDb)
        db = client[database_name]
    
        # Simple query on bounce collection
        collection = db[collection_name]
        result = list(collection.find({}))
        
        return result
    except Exception as e:
        return {"error": str(e)}
 
if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python3 mongoQueryFullCollection.py <database_name> <collection_name> len: ",len(sys.argv))
        sys.exit(1)
    database_name = sys.argv[1]
    # Split the collection name into journey and bounce collections
    collections = sys.argv[2]
    #database_name="test"
    #collections="aatriTest1"
    if len(collections) <=0:
        print("Error: <collection_name> should be specified correctly.")
        sys.exit(1)
    
    result = query_mongodb(database_name, collections)

    print(json.dumps(result, default=json_util.default))
