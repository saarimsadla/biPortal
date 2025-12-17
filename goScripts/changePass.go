package main
 
 // go run changePass.go sasadla sasadla sadla1
 
import (
	"context"
	"fmt"
	"log"
	"os"
	"time"
 
	"go.mongodb.org/mongo-driver/bson"
	"go.mongodb.org/mongo-driver/mongo"
	"go.mongodb.org/mongo-driver/mongo/options"
)
 
func updatePassword(collection *mongo.Collection, ctx context.Context, username, lastPassword, newPassword string) error {
	filter := bson.M{"username": username, "password": lastPassword}
	update := bson.M{"$set": bson.M{"password": newPassword}}
 
	result, err := collection.UpdateOne(ctx, filter, update)
	if err != nil {
		return err
	}
 
	if result.ModifiedCount == 0 {
		return fmt.Errorf("No matching document found for username and last password")
	}
 
	return nil
}

const ( 
	mongoURI = "mongodb://hersadmin:P0rt0f!n0@mongoa.pecosys.com:27017/"  
	dbName = "hursPortal"
	collectionName = "actor"
	//batchSize = 1999 // Adjust batch size based on your CSV file and system resources 
	//timeToLive = 2764800 //2764800 seconds for 32 days time to live
) 
 
func main() {
	
 
	// Create a MongoDB client
	client, err := mongo.NewClient(options.Client().ApplyURI(mongoURI))
	if err != nil {
		log.Fatal(err)
	}
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()
 
	// Connect to MongoDB
	err = client.Connect(ctx)
	if err != nil {
		log.Fatal(err)
	}
	defer client.Disconnect(ctx)
 
	// Access the specified database and collection
	collection := client.Database(dbName).Collection(collectionName)
 
	// Get input values from command line arguments or any other source
	username := os.Args[1]
	lastPassword := os.Args[2]
	newPassword := os.Args[3]
 
	// Update password
	err = updatePassword(collection, ctx, username, lastPassword, newPassword)
	if err != nil {
		log.Fatal(err)
	}
 
	fmt.Println("Password updated successfully!")
}