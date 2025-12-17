import sys
import pymongo
import json
 
def query_mongodb(database_name, collection_name, query):
    try:
        # MongoDB connection
        mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
        #mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.planetecosystems.com:27017/"
        client = pymongo.MongoClient(mongoDb)
        db = client[database_name]
        collection = db[collection_name]
 
        # Perform the query
        result = list(collection.find(query))
 
        return result
    except Exception as e:
        return {"error": str(e)}
 
if __name__ == "__main__":
    if len(sys.argv) != 4:
        print("Usage: python3 mongodb_query.py <database_name> <collection_name> <query>")
        sys.exit(1)
   
    database_name = sys.argv[1]
    collection_name = sys.argv[2]
    query =  json.loads(sys.argv[3])
 
    result = query_mongodb(database_name, collection_name, query)
    print(json.dumps(result))