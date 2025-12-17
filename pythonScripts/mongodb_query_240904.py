import sys
import pymongo
import json
 
# python3.9 /var/www/html/hursPortal/pythonScripts/mongodb_query.py salesForce journey_details.bounce_details '{"wlId":46, "eventId":35, "transactionSr":2}' 'a' '[<your_aggregation_pipeline_here>]'
 
def query_mongodb(database_name, journey_collection_name, bounce_collection_name, query, typ, ag):
    try:
        # MongoDB connection
        mongoDb = "mongodb://hersadmin:P0rt0f!n0@sandbox19.pecosys.com:27017/"
        client = pymongo.MongoClient(mongoDb)
        db = client[database_name]
        if typ == "q":
            # Simple query on bounce collection
            collection = db[journey_collection_name]
            result = list(collection.find(query))
            return result
        elif typ == "a":
            # Aggregation query
            wlId = int(query.get('wlId'))
            eventId = int(query.get('eventId'))
            transactionSr = query.get('transactionSr')
            eventType = 'Pre-Event' if int(transactionSr) == 1 else 'Post-Event'
            productType = 'BDR'
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
        return {"error": str(e)}
 
if __name__ == "__main__":
    if len(sys.argv) != 6:
        print("Usage: python3 mongodb_query.py <database_name> <journey_collection_name>.<bounce_collection_name> <initial_query_json> <typ> <ag>")
        sys.exit(1)
    database_name = sys.argv[1]
    # Split the collection name into journey and bounce collections
    collections = sys.argv[2].split('.')
    if len(collections) != 2:
        print("Error: <journey_collection_name>.<bounce_collection_name> should be specified correctly.")
        sys.exit(1)
    journey_collection_name = collections[0]
    bounce_collection_name = collections[1]
    query = json.loads(sys.argv[3])
    typ = sys.argv[4]
    ag = json.loads(sys.argv[5])
    result = query_mongodb(database_name, journey_collection_name, bounce_collection_name, query, typ, ag)
    print(json.dumps(result))