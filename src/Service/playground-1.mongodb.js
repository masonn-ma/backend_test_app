/* global use, db */
// MongoDB Playground
// Use Ctrl+Space inside a snippet or a string literal to trigger completions.

const database = 'NEW_DATABASE_NAME';
const collection = 'NEW_COLLECTION_NAME';

// Create a new database.
use(database);

// Create a new collection.
db.createCollection(collection);

// The prototype form to create a collection:
// Create users collection with validation schema
db.createCollection("users", {
    validator: {
        $jsonSchema: {
            bsonType: "object",
            required: ["username", "email", "passwordHash", "createdAt"],
            properties: {
                _id: {
                    bsonType: "objectId",
                    description: "Unique identifier for the user"
                },
                username: {
                    bsonType: "string",
                    pattern: "^[a-zA-Z0-9_]{3,20}$",
                    description: "Username must be 3-20 characters"
                },
                email: {
                    bsonType: "string",
                    pattern: "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}$",
                    description: "Valid email address"
                },
                passwordHash: {
                    bsonType: "string",
                    description: "Hashed password"
                },
                firstName: {
                    bsonType: "string",
                    description: "User's first name"
                },
                lastName: {
                    bsonType: "string",
                    description: "User's last name"
                },
                fullName: {
                    bsonType: "string",
                    description: "User's full name"
                },
                age: {
                    bsonType: "int",
                    minimum: 13,
                    description: "User's age"
                },
                dateOfBirth: {
                    bsonType: "date",
                    description: "User's date of birth"
                },
                gender: {
                    enum: ["male", "female", "other", "prefer not to say", null],
                    description: "User's gender"
                },
                phoneNumber: {
                    bsonType: "string",
                    description: "Phone number with country code"
                },
                address: {
                    bsonType: "object",
                    properties: {
                        street: { bsonType: "string" },
                        city: { bsonType: "string" },
                        state: { bsonType: "string" },
                        postalCode: { bsonType: "string" },
                        country: { bsonType: "string" }
                    }
                },
                profilePicture: {
                    bsonType: "string",
                    description: "URL to profile picture"
                },
                bio: {
                    bsonType: "string",
                    maxLength: 500,
                    description: "User's biography"
                },
                roles: {
                    bsonType: "array",
                    items: {
                        bsonType: "string",
                        enum: ["user", "admin", "moderator", "guest"]
                    }
                },
                permissions: {
                    bsonType: "array",
                    items: { bsonType: "string" },
                    description: "Array of permission strings"
                },
                isActive: {
                    bsonType: "bool",
                    description: "Whether the user account is active"
                },
                isEmailVerified: {
                    bsonType: "bool",
                        description: "Whether email has been verified"
                },
                isPhoneVerified: {
                    bsonType: "bool",
                            description: "Whether phone number has been verified"
                },
                lastLogin: {
                    bsonType: "date",
                    description: "Timestamp of last login"
                },
                loginCount: {
                    bsonType: "int",
                    description: "Number of times user has logged in"
                },
                createdAt: {
                    bsonType: "date",
                    description: "Account creation timestamp"
                },
                updatedAt: {
                    bsonType: "date",
                    description: "Last update timestamp"
                },
                deletedAt: {
                    bsonType: ["date", "null"],
                    description: "Soft delete timestamp"
                },
                socialLogin: {
                    bsonType: "object",
                    properties: {
                        google: { bsonType: ["string", "null"] },
                        facebook: { bsonType: ["string", "null"] },
                        twitter: { bsonType: ["string", "null"] }
                    }
                },
                preferences: {
                    bsonType: "object",
                    properties: {
                        language: { bsonType: "string" },
                        theme: { bsonType: "string", enum: ["light", "dark", "system"] },
                        notifications: {
                            bsonType: "object",
                            properties: {
                                email: { bsonType: "bool" },
                                push: { bsonType: "bool" },
                                sms: { bsonType: "bool" }
                            }
                        }
                    }
                },
                twoFactorEnabled: {
                    bsonType: "bool",
                    description: "Whether two-factor authentication is enabled"
                },
                twoFactorSecret: {
                    bsonType: ["string", "null"],
                    description: "Secret for 2FA"
                },
                resetPasswordToken: {
                    bsonType: ["string", "null"],
                    description: "Token for password reset"
                },
                resetPasswordExpires: {
                    bsonType: ["date", "null"],
                    description: "Expiration time for reset token"
                }
            }
        }
    },
    validationLevel: "strict",
    validationAction: "error"
});

// Create indexes for better query performance
db.users.createIndex({ email: 1 }, { unique: true });
db.users.createIndex({ username: 1 }, { unique: true });
db.users.createIndex({ createdAt: -1 });
db.users.createIndex({ roles: 1 });
db.users.createIndex({ "address.country": 1, "address.city": 1 });

// Insert sample users
db.users.insertMany([
    {
        username: "johndoe",
        email: "john.doe@example.com",
        passwordHash: "$2b$10$hashedpasswordplaceholder",
        firstName: "John",
        lastName: "Doe",
        fullName: "John Doe",
        age: 28,
        dateOfBirth: new Date("1996-05-15"),
        gender: "male",
        phoneNumber: "+1234567890",
        address: {
            street: "123 Main St",
            city: "New York",
            state: "NY",
            postalCode: "10001",
            country: "USA"
        },
        profilePicture: "https://example.com/images/johndoe.jpg",
        bio: "Software developer and tech enthusiast",
        roles: ["user"],
        permissions: ["read", "write"],
        isActive: true,
        isEmailVerified: true,
        isPhoneVerified: false,
        lastLogin: new Date(),
        loginCount: 15,
        createdAt: new Date("2023-01-15"),
        updatedAt: new Date(),
        socialLogin: {
            google: null,
            facebook: null,
            twitter: null
        },
        preferences: {
            language: "en",
            theme: "dark",
            notifications: {
                email: true,
                push: true,
                sms: false
            }
        },
        twoFactorEnabled: false
    },
    {
        username: "janedoe",
        email: "jane.doe@example.com",
        passwordHash: "$2b$10$hashedpasswordplaceholder",
        firstName: "Jane",
        lastName: "Doe",
        fullName: "Jane Doe",
        age: 32,
        dateOfBirth: new Date("1992-08-20"),
        gender: "female",
        phoneNumber: "+0987654321",
        address: {
            street: "456 Oak Ave",
            city: "Los Angeles",
            state: "CA",
            postalCode: "90001",
            country: "USA"
        },
        profilePicture: "https://example.com/images/janedoe.jpg",
        bio: "Product manager and team lead",
        roles: ["user", "moderator"],
        permissions: ["read", "write", "moderate"],
        isActive: true,
        isEmailVerified: true,
        isPhoneVerified: true,
        lastLogin: new Date(),
        loginCount: 42,
        createdAt: new Date("2022-11-10"),
        updatedAt: new Date(),
        socialLogin: {
            google: "google-id-12345",
            facebook: null,
            twitter: null
        },
        preferences: {
            language: "en",
            theme: "light",
            notifications: {
                email: true,
                push: false,
                sms: true
            }
        },
        twoFactorEnabled: true
    },
    {
        username: "adminuser",
        email: "admin@example.com",
        passwordHash: "$2b$10$hashedpasswordplaceholder",
        firstName: "Admin",
        lastName: "User",
        fullName: "Admin User",
        age: 40,
        dateOfBirth: new Date("1984-03-05"),
        gender: "prefer not to say",
        phoneNumber: "+1122334455",
        address: {
            street: "789 Admin Blvd",
            city: "San Francisco",
            state: "CA",
            postalCode: "94102",
            country: "USA"
        },
        profilePicture: "https://example.com/images/admin.jpg",
        bio: "System administrator",
        roles: ["user", "admin"],
        permissions: ["read", "write", "moderate", "delete", "admin"],
        isActive: true,
        isEmailVerified: true,
        isPhoneVerified: true,
        lastLogin: new Date(),
        loginCount: 156,
        createdAt: new Date("2021-06-01"),
        updatedAt: new Date(),
        socialLogin: {
            google: null,
            facebook: null,
            twitter: null
        },
        preferences: {
            language: "en",
            theme: "system",
            notifications: {
                email: true,
                push: true,
                sms: true
            }
        },
        twoFactorEnabled: true
    }
]);

// Query to verify users were inserted
print("Users inserted successfully!");
db.users.find({}, {
    username: 1,
    email: 1,
    fullName: 1,
    roles: 1,
    isActive: 1,
    createdAt: 1
}).forEach(printjson);
/* db.createCollection( <name>,
  {
    capped: <boolean>,
    autoIndexId: <boolean>,
    size: <number>,
    max: <number>,
    storageEngine: <document>,
    validator: <document>,
    validationLevel: <string>,
    validationAction: <string>,
    indexOptionDefaults: <document>,
    viewOn: <string>,
    pipeline: <pipeline>,
    collation: <document>,
    writeConcern: <document>,
    timeseries: { // Added in MongoDB 5.0
      timeField: <string>, // required for time series collections
      metaField: <string>,
      granularity: <string>,
      bucketMaxSpanSeconds: <number>, // Added in MongoDB 6.3
      bucketRoundingSeconds: <number>, // Added in MongoDB 6.3
    },
    expireAfterSeconds: <number>,
    clusteredIndex: <document>, // Added in MongoDB 5.3
  }
)*/

// More information on the `createCollection` command can be found at:
// https://www.mongodb.com/docs/manual/reference/method/db.createCollection/
