# AzuraCast Blesta Module

## Overview
This Blesta module allows administrators to provision and manage radio stations on [AzuraCast](https://www.azuracast.com/) directly from the Blesta interface. The module integrates with the AzuraCast API to create stations, manage users, and provide clients with single-click login access to their radio station dashboards.

## Features
- **Create Radio Stations**: Automatically create new radio stations for clients using the AzuraCast API.
- **Automatic Login**: Clients can log into AzuraCast without needing to manually enter credentials.
- **Admin Configuration**: Set up and manage AzuraCast API keys and station URLs directly from the Blesta admin panel.

## Requirements
- Blesta 4.0+ (ensure your Blesta version is up-to-date)
- An AzuraCast installation with API access enabled
- A valid AzuraCast API key with permissions to create stations and manage users

## Installation

1. **Upload the Module**:
    - Extract the module files to your Blesta `/components/modules/` directory.
    - Ensure the folder structure is as follows:

    ```
    /components/modules/azuracast_module/
        ├── azuracast_module.php
        ├── language/
        │   └── en_us.php
        └── views/
            └── default/
                ├── client_main.pdt
                ├── admin_main.pdt
    ```

2. **Activate the Module**:
    - Log into the Blesta admin panel.
    - Navigate to **Settings > Company > Modules**.
    - Find the "AzuraCast Module" in the list and click **Install**.

3. **Configure the Module**:
    - After installation, click on the "AzuraCast Module" to access the module configuration.
    - Enter your AzuraCast instance’s **Station URL** (e.g., `https://yourstation.com`) and **API Key**.

4. **Create a Package**:
    - Go to **Packages > Create Package**.
    - Select "AzuraCast Module" from the Module dropdown.
    - Configure the package as desired.

## Usage

### Admin Configuration
To configure the AzuraCast module, follow these steps:
1. Navigate to **Settings > Company > Modules** and select "AzuraCast Module."
2. Enter the **Station URL** (the URL of your AzuraCast instance) and **API Key** (your AzuraCast API access key).
3. Click **Save** to store your settings.

### Client View
Once a station is created for a client, they can view their station details and log in to AzuraCast from the client area:
1. Go to **Services** in the client dashboard.
2. Click on the radio station service.
3. Click the **Login to AzuraCast** button to be redirected to their AzuraCast station dashboard without needing to log in.

## Troubleshooting

### Common Issues
1. **API Errors**: 
    - Ensure the API URL and API Key are correct and have sufficient permissions.
    - Check that your AzuraCast instance is accessible from the Blesta server.
  
2. **Database Issues**:
    - Ensure the database tables are created properly. You can verify by checking the `azuracast_login_tokens` table in your Blesta database.

3. **Redirection Issues**:
    - Make sure no output is sent before the `header("Location: ...")` redirect function, as this can cause header issues.
    - Verify that the AzuraCast API accepts the login token correctly.

## Contributing
If you’d like to contribute to this project, feel free to open issues or submit pull requests. Ensure that all code adheres to Blesta module development standards.

## License
This module is open-source under the MIT License. You’re free to modify and distribute it, but you must include this original license file in any distributions.
