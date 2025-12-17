// connection parameters
//	database := "hursPortal"
//	collectionName := "actor"
//run as ubuntu user on sandbox8 sudo su - ubuntu
//run comand go run authConfirm.go username password // for analysis file
//run comand go run authConfirm.go sasadla sasadla
// results come out to lgnRes.txt
// go mod init <moduleName>
//go get go.mongodb.org/mongo-driver/mongo

package main
import (
	//"bufio"
	//"encoding/csv" 
	"strconv"
	//"sync"
	"fmt"
    //"time"
	"os"
	"log"
	"context"
	"strings"
	//"regexp"
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/bson/primitive"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
	//"database/sql"
    //_ "github.com/go-sql-driver/mysql"

)

const ( 
	dbUri = "mongodb://sasadla:sasadla123*@localhost:27017/"  
	//dbUri = "mongodb://dataWriter:dataWriter@localhost:27017"
	dbName = "hursPortal"
	//batchSize = 1999 // Adjust batch size based on your CSV file and system resources 
	//timeToLive = 2764800 //2764800 seconds for 32 days time to live
) 

//var logDbConStrng string // global variable for connection string of mysql db
//var fType string // A :-> analysis file,C :-> all Csv files, O :-> out files

func main() {

	// Check the number of arguments passed 
	if len(os.Args) < 2 { 
		fmt.Println("Usage: go run main.go <username> <password>") 
		return 
	} 
	// Access the command-line arguments 
	args := os.Args[1:] 
	// Process the arguments as needed 
	//for i, arg := range args {
	var lgnUsername 	string 
	var lgnPassword 	string
	//var res			 	string
	
	lgnUsername 		= args[0]
	lgnPassword 		= args[1]
	
	userTable := "actor"
	itemTable := "scripts"
	rigtTable := "roles"
	
	// Open a file for writing logs
	/*file, err := os.OpenFile("authConfirmlog.txt", os.O_CREATE|os.O_WRONLY|os.O_APPEND, 0666)
	if err != nil {
		log.Fatal("Error opening log file: ", err)
	}
	defer file.Close()
 
	// Set the output of log to the file
	log.SetOutput(file)
 */
	
	
	client, ctx, datbse := connectToMongoDB()
	
	collection := getCollection(client,userTable,datbse)
	 
	res, stat, uid := authenticateUser (collection,ctx ,lgnUsername, lgnPassword)
	
	//fmt.Printf("\n Result: %+v\n", res)
	
	if stat == 1 { 
		// call code to fetch user rights here
		//fmt.Printf("\n Result: %+v\n", res)
		// write right array to res file
		collectionItm := getCollection(client,itemTable,datbse)
		collectionRgt := getCollection(client,rigtTable,datbse)
		
		rs, st := getUserRights(collectionItm ,collectionRgt , ctx , uid)
		if st == 0 {
			res = "error logging in: <" + rs + ">"
		} else {
			res += rs
		}
	} else {
		// exit here
		res += ",\"rolse\":[{}]"
		// write code to output file for php to pickup file name lgnRes.txt
		
	}
	
	res += "}"
	
	//createResFile ("lgnRes.txt",res){
	
	fmt.Println(res)
	//fmt.Printf("\n uid: %+v\n", uid)
	
	
	
	//readAndPrintCollection(collection,ctx)
	
	
	//deleteManyFromCollection(collection,collName)
	
	//uploadCsvToCollection(fLocANme, fType, runId, collection, ctx)
	
	//updateManyInCollection (collection, ctx)
	
//	checkDeleteAndCreateTTLIndex(collection, ctx, collName)	
	
		
	
	closeConnection(client ,ctx)
}

/*func updateStatusToLog(dbConStrng string, messageType string,logMessage string, errM error) error {
	var insrtQry string
	// Open up our database connection.
    db, err := sql.Open("mysql", dbConStrng)
    // if there is an error opening the connection, handle it
    if err != nil {
        panic(err.Error())
    }

	if messageType == "P"{
		insrtQry = "INSERT INTO xcel_new_monthly_savings_extract_log(val,descr) VALUES ('MONGO_UPLOAD_STATUS','"+ logMessage +"')"
	}else if messageType == "F"{
		insrtQry = "INSERT INTO xcel_new_monthly_savings_extract_log(val,descr) VALUES ('MONGO_UPLOAD_STATUS','An error was encountered: " + logMessage + "|" + errM.Error() +"')"
	}	
	
	// perform a db.Query insert
    insert, err := db.Query(insrtQry)
    // if there is an error inserting, handle it
    if err != nil {
        panic(err.Error())
    }
    // be careful deferring Queries if you are using transactions
    defer insert.Close()
	
	// defer the close till after the main function has finished
    // executing
    defer db.Close()
	
	if messageType == "P"{
		log.Println(logMessage)
	}else if messageType == "F"{
		log.Fatal(logMessage,errM)
	}	
	
	fmt.Printf("\n") 
	
	return err
}
*/
func connectToMongoDB() (*mongo.Client, context.Context, *mongo.Database){
	//ctx, cancel := context.WithTimeout(context.Background(),3600*time.Second)
	ctx := context.Background()
	clientOptions := options.Client().ApplyURI(dbUri) 
	client, err := mongo.Connect(ctx, clientOptions)
	datbse := client.Database(dbName)
	if err != nil { 
		log.Printf("Error connecting to MongoDB:", err) 
		//err:= updateStatusToLog(logDbConStrng, "F", "Error connecting to MongoDB:",err)
		//if err != nil {
		//	panic(err.Error())
		//}

	} 
	
	return client, ctx, datbse
}

func getCollection(client *mongo.Client,collName string, datbse *mongo.Database) (*mongo.Collection){
	// Get the collection to insert the data
	collection := datbse.Collection(collName) 
	
	return collection
}

func closeConnection(client *mongo.Client,ctx context.Context){
	client.Disconnect(ctx)
}

func authenticateUser(collection *mongo.Collection, ctx context.Context, lngUser string, lgnPass string) (string, int, string) {
 
	// Find a user by username
	usernameToFind := lngUser
	filter := bson.M{"username": usernameToFind}
	res := ""
	stat := 0
	uid := ""
 
	// Find the user and retrieve the entire document
	result := collection.FindOne(ctx, filter)
	if result.Err() == mongo.ErrNoDocuments {
		//log.Printf("",usernameToFind + " User not found.")
		res = "User not found"
		return res, stat, uid
	} else if result.Err() != nil {
		log.Printf("1.Fatal error authentication: ", result.Err())
	}
 
	// Decode the result into a map
	var user map[string]interface{}
	err := result.Decode(&user)
	if err != nil {
		log.Printf("2.Fatal error authentication: ", err)
	}
 
	// Print the entire document
	//fmt.Printf("User Document: %+v\n", user)
	//fmt.Printf("\n User Name: %+v\n", user["username"])
	//fmt.Printf("\n Password: %+v\n", user["password"])
//	fmt.Printf("\n id: %+v\n", user["_id"])
//	fmt.Printf("\n id: %+v\n", user["defWhitLabel"])
 
	// Check if "_id" is present in the document
	if id, ok := user["_id"]; ok {
		switch id := id.(type) {
		case primitive.ObjectID:
			uid = id.Hex()
		case string:
			uid = id
		case int32:
			uid = strconv.Itoa(int(id))
		default:
			res = fmt.Sprintf("Authentication Error: Unexpected type for _id: %T", id)
			log.Printf("","Authentication Error: Unexpected type for _id: %T", id)
			return res, stat, uid
		}
	} else {
		res = "Authentication Error: _id not found in the document"
		log.Printf("" ,"Authentication Error: _id not found in the document")
		return res, stat, uid
	}
 
	name, nameOk := user["name"].(string)
	password, passwordOk := user["password"].(string)
	defWhiteLabel, defWhiteLabelOk := user["defWhitLabel"]
	
 
	if !nameOk || !passwordOk || !defWhiteLabelOk {
		res = "Authentication Error: Unable to convert user data to string"
		log.Printf("","Authentication Error: Unable to convert user data to string")
		uid = ""
		return res, stat, uid
	}
	
	//fmt.Printf("\n ss; %+v\n",defWhiteLabel)
	
	dwV, ok := defWhiteLabel.(int32)
	
	if !ok {
		res = "Authentication Error: Unable to convert whitelabel data to string"
		log.Printf("","Authentication Error: Unable to convert whitelabel data to string")
	}
	
	defWhiteLbl := strconv.Itoa(int(dwV))
 
	res += "{\"User_Found\":\"" + name + "\","
	if lgnPass == password {
		res += "\"Password_Matched\":\"Y\","
		res += "\"defWhiteLabel\":\""+ defWhiteLbl +"\""
		stat = 1
	} else {
		res += "\"Password_Matched\":\"N\""
		stat = 0
		uid = ""
	}
 
	return res, stat, uid
}

func getUserRights(collectionItm *mongo.Collection, collectionRgt *mongo.Collection, ctx context.Context, uid string) (string, int) {
	// Fetch all active items list
	optnToFind := "Y"
	filter := bson.M{"active": optnToFind}
	stat := 0
 
	// Find all documents that match the filter
	cursor, err := collectionItm.Find(ctx, filter)
	if err != nil {
		log.Fatal("1.Fatal error Fetch rights: ", err)
	}
	defer cursor.Close(ctx)
 
	// Create a slice to store the _id values
	var idValues []interface{}
 
	// Iterate over the results
	for cursor.Next(ctx) {
		var objEcts map[string]interface{}
		if err := cursor.Decode(&objEcts); err != nil {
			log.Fatal("2.Fatal error Fetch rights: ", err)
		}
 
		// Print the entire document
		//fmt.Printf("Items Document: %+v\n", objEcts)
 
		// Access the "_id" field and store it in the slice
		if id, ok := objEcts["_id"]; ok {
			idValues = append(idValues, id)
		}
	}
 
	// Check for errors from iterating over the cursor
	if err := cursor.Err(); err != nil {
		log.Fatal("3.Fatal error Fetch rights: ", err)
	}
 
	// Now you have idValues containing all the "_id" values from collectionItm
	// Use idValues as a filter to fetch data from collectionRgt
 
	// Create maps to store unique values for "whitelabel" and "itemID"
	uniqueWhitelabels := make(map[string]struct{})
	uniqueItemIDs := make(map[string]struct{})
 
	i, err := strconv.Atoi(uid)
    if err != nil {
        log.Printf("1.Panic error Fetch rights: ",err)
		res := "4. unable to fetch rights,consult Hurs Technical team"
		return res, stat 
    }
	
	// For example, construct a filter for the second collection
	filter2 := bson.M{
		"itemID": bson.M{"$in": idValues},
		"uid":    i,
		"Active": "Y",
	}
	// Fetch data from the second collection using the filter
	cursor2, err := collectionRgt.Find(ctx, filter2)
	if err != nil {
		log.Fatal("4.Fatal error Fetching data from collectionRgt: ", err)
	}
	defer cursor2.Close(ctx)
 
	// Iterate over the results from the second collection
	for cursor2.Next(ctx) {
		var objRcts map[string]interface{}
		if err := cursor2.Decode(&objRcts); err != nil {
			log.Fatal("5.Fatal error Fetching data from collectionRgt: ", err)
		}
 
		// Ensure uniqueness for "whitelabel"
		whitelabelValue, ok := objRcts["whitelabel"]
		if ok {
			// Use fmt.Sprintf to convert the value to string
			stringValue := fmt.Sprintf("%v", whitelabelValue)
			uniqueWhitelabels[stringValue] = struct{}{}
		}
		 
		// Ensure uniqueness for "itemID"
		itemIDValue, ok := objRcts["itemID"]
		if ok {
			// Use fmt.Sprintf to convert the value to string
			stringValue := fmt.Sprintf("%v", itemIDValue)
			uniqueItemIDs[stringValue] = struct{}{}
		}
 
		// Print the entire document from the second collection
	//	fmt.Printf("Rights Document: %+v\n", objRcts)
	}
 
	// Check for errors from iterating over the cursor
	if err := cursor2.Err(); err != nil {
		log.Fatal("6.Fatal error Fetching data from collectionRgt: ", err)
	}
 
	// Convert unique values to slices
	var distinctWhitelabels []string
	var distinctItemIDs []string
	for key := range uniqueWhitelabels {
		distinctWhitelabels = append(distinctWhitelabels, key)
	}
	for key := range uniqueItemIDs {
		distinctItemIDs = append(distinctItemIDs, key)
	}
 
	// Join distinct values for "whitelabel" and "itemID" into comma-separated strings
	whitelabelString := strings.Join(distinctWhitelabels, ", ")
	itemIDString := strings.Join(distinctItemIDs, ", ")
	
	//fmt.Printf("Whitlabel rights: %+v\n", whitelabelString)
	//fmt.Printf("item rights: %+v\n", itemIDString)
	res := ",\"rolse\":[{\"whiteLabels\":\""+whitelabelString+"\"}, {\"rights\":\""+itemIDString+"\"}]"
	//fmt.Printf("rolse: %+v\n", res)
	stat = 1
	
	return res, stat
}


func createResFile (fName string, res string){
	// Specify the file path
	filePath := fName
 
	// Check if the file exists
	if _, err := os.Stat(filePath); err == nil {
		// File exists, remove it
		err := os.Remove(filePath)
		if err != nil {
			fmt.Println("Error removing existing file:", err)
			return
		}
		fmt.Println("Existing file removed")
	}
 
	// Create a new file
	file, err := os.Create(filePath)
	if err != nil {
		fmt.Println("Error creating file:", err)
		return
	}
	defer file.Close()
 
	// Write content to the new file
	content := res
	_, err = file.WriteString(content)
	if err != nil {
		fmt.Println("Error writing to file:", err)
		return
	}
 
	fmt.Println("File created and written successfully")
}

/*
// Function to check if a collection exists.
func collectionExists(database *mongo.Database, collName string, ctx context.Context) (bool, error) {
	//ctx, cancel := context.WithTimeout(ctx, 2*time.Second)
	//defer cancel()

 

	filter := bson.D{{"name", collName}}
	cursor, err := database.ListCollections(ctx, filter)
	if err != nil {
		err:= updateStatusToLog(logDbConStrng, "F", "Failed to retrieve collection list:",err)
		if err != nil {
			panic(err.Error())
		}
		return false, fmt.Errorf("failed to retrieve collection list: %v", err)
	}

 

	// Check if the collection name exists in the list.
	for cursor.Next(ctx) {
		var result bson.M
		if err := cursor.Decode(&result); err != nil {
			err:= updateStatusToLog(logDbConStrng, "F", "Failed to decode collection:",err)
			if err != nil {
				panic(err.Error())
			}
			return false, fmt.Errorf("failed to decode collection: %v", err)
		}
		if result["name"] == collName {
			return true, nil
		}
	}

 

	return false, nil
}

// Function to create a collection.
func createCollection(datbse *mongo.Database, collName string, ctx context.Context) error {
	opts := options.CreateCollection()
	err := datbse.CreateCollection(ctx, collName, opts)
	return err
}

func deleteManyFromCollection(collection *mongo.Collection,collName string){
	//clear all data present in the specified collection
	deleteResult, err := collection.DeleteMany(context.TODO(), bson.D{{}})
	if err != nil {
		err:= updateStatusToLog(logDbConStrng, "F", "Error Deleting from collection:",err)
		if err != nil {
			panic(err.Error())
		}
	}
	errL:= updateStatusToLog(logDbConStrng, "P", "Deleted "+strconv.FormatInt(int64(deleteResult.DeletedCount),10)+" documents in the "+collName+" collection",nil)
	if errL != nil {
		panic(errL.Error())
	}
}

func uploadCsvToCollection(fLocANme string,fType string,runId string,collection *mongo.Collection,ctx context.Context){
	// Open the CSV file 
	csvfile, err := os.OpenFile(fLocANme, os.O_RDONLY, os.ModePerm) 
	if err != nil { 
		err:= updateStatusToLog(logDbConStrng, "F", "Error opening the CSV file:",err)
		if err != nil {
			panic(err.Error())
		}
	} 
	defer csvfile.Close() 
	
	// Create a new scanner to read the CSV file 
	scanner := bufio.NewScanner(csvfile) 
	scanner.Scan() //this move to the next line so as to fetch headings
	heading := scanner.Text() //this fetches the heading text to generically parse the csv rather than creating structs
	
	var wg sync.WaitGroup 
	var batch []interface{} 
	count := 0 
	cnt := 0 
	
	if fType == "A" {  // since the first two lines in the analysis file are the file names the data is from as such the scanner increments twice before reading in the actual headings, and adds the first two lines to the batch
		heading1 := heading
		scanner.Scan()
		heading2 := scanner.Text()
		scanner.Scan()
		heading = scanner.Text()
		hdsT := "_id,RUN_ID," + heading
		heading = "_id,RUN_ID," + heading + ",createdBy,creationDate"
		cnt++
		d := parseCSVRow(runId,heading,heading1,cnt)
		batch = append(batch, d)
		count++
		cnt++
		dta1 := parseCSVRow(runId,heading,heading2,cnt)
		batch = append(batch, dta1)
		count++
		heading = hdsT
	}else if fType == "O" {
		heading = "_id,RUN_ID," + heading
	}else{
		heading = "RUN_ID," + heading
	}
	
	heading = heading + ",createdBy,creationDate"
	
	
	
	// Read the CSV data and upload in batches 
	for scanner.Scan() { 
		row := scanner.Text() 
		if fType == "A" && strings.Contains(row, ";"){
			continue
		}else if fType == "A" && strings.Contains(row, "missing") {
			heading = "_id,RUN_ID," + row + ",createdBy,creationDate"
			scanner.Scan()
		}
		
		if fType == "A" || fType == "O" {
			cnt++
			//fmt.Printf("Ftype is: %s and count updated to: %s\n\n", fType,cnt) 
		}else {
			cnt = 0
		}

		data := parseCSVRow(runId,heading,row,cnt) // Custom function to parse CSV row into a map[string]interface{} 
		batch = append(batch, data) 
		count++ 
			
		
		if count == batchSize { 
			// Upload the batch to MongoDB using a goroutine 
			wg.Add(1) 
			go func(docs []interface{}) { 
				defer wg.Done() 
				_, err := collection.InsertMany(ctx, docs) 
				if err != nil { 
					err:= updateStatusToLog(logDbConStrng, "F", "Error inserting data into MongoDB:",err)
					if err != nil {
						panic(err.Error())
					}
				} 
			}(batch) 
				
				// Clear the batch and reset the count 
				batch = nil 
				count = 0 
		} 
	} 
	
	
	// Upload any remaining documents in the last batch 
	if len(batch) > 0 {
		_, err = collection.InsertMany(ctx, batch) 
		if err != nil { 
			err:= updateStatusToLog(logDbConStrng, "F", "Error inserting remaining data into MongoDB:",err)
			if err != nil {
				panic(err.Error())
			}
		} 
	} 
	
	// Wait for all goroutines to finish 
	wg.Wait() 
	
	
	log.Println("Data upload completed\n") 
}


// Custom function to parse CSV row into a map[string]interface{} 
func parseCSVRow(rnId string,hdng string,row string,cnt int) map[string]interface{} { 
	// Implement your custom parsing logic here based on the CSV structure 
	// For example, split the row by commas and create a map from the headers 
	// to the values. 
	// Sample implementation: 
	data := make(map[string]interface{}) 
	hds := strings.Split(hdng, ",")
	//utcT := time.Now().UTC() //utcT.String()
	if fType == "A" || fType == "O"{
		row = strconv.Itoa(cnt)+","+rnId + "," + row + ",extractUploadScript,"
	}else{
		row = rnId + "," + row + ",extractUploadScript,"
	}
	fields := strings.Split(row, ",")
	
	//fmt.Printf("Heading is: %s\n\n", hdng) 
	//fmt.Printf("row is: %s\n\n", row) 
	
	for i, hd := range hds {
		//fmt.Println(index, a)
		//,createdBy,creationDate,updatedBy,updationDate
		if len(hd) > 0 {
			data[hd] = fields[i]
		}
	}
	
	//data["RUN_ID"] = fields[0] 
	//data["PARAM_NAME"] = fields[1] 
	//data["PARAM_VALUE"] = fields[2] 
	//data["PARAM_DESC"] = fields[3] 
	//data["PARAM_DATE"] = fields[4] 
	//data["TYP"] = fields[5]
		
	
	return data 
}

func updateManyInCollection (collection *mongo.Collection,ctx context.Context){
	log.Println("Updating creation date in documents\n") 
	
	
	filter := bson.M{"creationDate": bson.M{"$type": "string"}}
	
	update := bson.M{"$set": bson.M{"creationDate": time.Now()}}
	
	updateResult, err := collection.UpdateMany(ctx, filter, update) 	
	if err != nil { 		
		err:= updateStatusToLog(logDbConStrng, "F", "Error updating data into MongoDB:",err)
		if err != nil {
			panic(err.Error())
		} 	
	}
	
	errL:= updateStatusToLog(logDbConStrng, "P", "Matched "+strconv.FormatInt(int64(updateResult.MatchedCount),10)+" documents and updated "+strconv.FormatInt(int64(updateResult.ModifiedCount),10)+" documents",nil)
	if errL != nil {
		panic(errL.Error())
	}

}

func checkDeleteAndCreateTTLIndex(collection *mongo.Collection,ctx context.Context, collName string){

	log.Println("Adding Ttl index for the data!\n")
	
	log.Println("Dropping existing ttl index for the data!\n")
	
	// List all indexes for the collection.
	indexView := collection.Indexes()
	cursor, err := indexView.List(ctx)
	if err != nil {
		log.Printf(err)
	}
	defer cursor.Close(ctx)

 
	ttlIndexExists := false
	indexName := ""
	
	// Search for the TTL index on the "creationDate" field.
	for cursor.Next(ctx) {
		var indexDoc bson.D
		if err := cursor.Decode(&indexDoc); err != nil {
			log.Printf(err)
		}

 

		// Check if this is the TTL index on the "creationDate" field.
		isTTLIndex := false
		for _, elem := range indexDoc {
			if elem.Key == "key" {
				// Check if the "creationDate" field is the TTL index.
				creationDateIndex := elem.Value.(bson.D)
				if creationDateIndex[0].Key == "creationDate" && creationDateIndex[0].Value == int32(1) {
					isTTLIndex = true
					break
				}
			}
		}

 

		if isTTLIndex {
			indexName = indexDoc.Map()["name"].(string)
			ttlIndexExists = true
			break
		}
	}
	
	if ttlIndexExists{
		_, err := collection.Indexes().DropOne(ctx, indexName)
		if err != nil {
			log.Printf(err)
		}
		errL:= updateStatusToLog(logDbConStrng, "P", "TTL index "+indexName+" on creationDate removed successfully",nil)
		if errL != nil {
			panic(errL.Error())
		}
	}else{
		errT:= updateStatusToLog(logDbConStrng, "P", "TTL index on creationDate Does not exists",nil)
		if errT != nil {
			panic(errT.Error())
		}
	}
 

	if err := cursor.Err(); err != nil {
		log.Printf(err)
	}
	
	log.Println("Creating Ttl index for the data!\n")
	
	// Define the TTL index options with the "creationDate" field and the desired expiration time.	
	// The expiration time specifies how long documents will be retained in seconds.	
	ttlIndexOptions := options.Index().SetExpireAfterSeconds(timeToLive) // Set the expiration time in seconds (1 hour).
	
	// Define the TTL index model.	
	ttlIndexModel := mongo.IndexModel{ 		
		Keys:    bson.D{{Key: "creationDate", Value: 1}}, // The field to create the index on with ascending order.		
		Options: ttlIndexOptions, 	
	}
	
	// Create the TTL index on the "creationDate" field.	
	_, err = collection.Indexes().CreateOne(ctx, ttlIndexModel) 	
	if err != nil { 		
		log.Printf(err) 	
	} 	
	
	errS:= updateStatusToLog(logDbConStrng, "P", "TTL index on "+collName+" created successfully",nil)
	if errS != nil {
		panic(errS.Error())
	}

}



func readAndPrintCollection(collection *mongo.Collection,ctx context.Context){

var items []bson.M // to read collection form mongo
	cur, err := collection.Find(ctx, bson.D{{}}) // to read collection form mongo
	if err != nil {
            log.Printf(err)
        }
        defer cur.Close(ctx)
       for cur.Next(ctx) {
            var raw interface{}
            err := cur.Decode(&raw)
            if err != nil {
                log.Printf(err)
            }
            //print element data from collection
            fmt.Printf("Element: %v\n", raw)
        } // to read collection form mongo *
		
		cur.All(ctx,&items) // to read collection form mongo
		fmt.Println(items) // to read collection form mongo
		
		
        if err := cur.Err(); err != nil {
            log.Printf(err)
        }
}

*/
