# MySQL Service Setup for Passbolt

## Steps to Complete Setup:

### 1. Create MySQL Service in Railway

1. Go to your Railway project: https://railway.com/project/59160035-518a-4418-8e17-12f2c264e16b
2. Click **"+ New"** → **"Database"** → **"MySQL"**
3. Name it "MySQL" (important for variable references)
4. Railway will automatically provision the MySQL database

### 2. Connect Services

The Passbolt service is already configured to use these reference variables:
- `DATASOURCES_DEFAULT_HOST` → Points to MySQL internal host
- `DATASOURCES_DEFAULT_PORT` → 3306
- `DATASOURCES_DEFAULT_USERNAME` → MySQL user
- `DATASOURCES_DEFAULT_PASSWORD` → MySQL password
- `DATASOURCES_DEFAULT_DATABASE` → MySQL database name

These use Railway's reference variables syntax: `${{MySQL.VARIABLE_NAME}}`

### 3. Deployment

Once MySQL service is created:
1. Railway will automatically redeploy Passbolt
2. Passbolt will connect to the MySQL service internally
3. Database migrations will run automatically
4. GPG keys will be generated on first run

### 4. Access Passbolt

After deployment:
- **URL**: https://passbolt.theportlandcompany.com
- **Setup**: Follow the setup wizard to create admin account
- **Browser Extension**: Install Passbolt browser extension

## Architecture

```
[User Browser] 
    ↓ HTTPS
[Railway Edge (SSL)]
    ↓ 
[Passbolt Service (Port 80)]
    ↓ Internal Network
[MySQL Service (Port 3306)]
```

## Troubleshooting

If connection fails:
1. Ensure MySQL service is named exactly "MySQL"
2. Check Railway logs for connection errors
3. Verify environment variables are using reference syntax
4. MySQL service must be in same project/environment