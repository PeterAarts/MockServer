# MockServer
As being part of an eco-system to showcase the abilities of vehicle event data provided by European OEM truck manufactures. The eco system consists of following components:
- rFMS Collector ( back-end scheduler to retrieve API-data from various interface like rFMS, RDW and OpenWeather.)
- rFMS Connect ( Front-end for the FleetManagement system -> https://rfmsconnect.nl or https://demo.rfmsconnect.nl)

# solution
This solution provides mocked data based on real vehicle event data. The real data will be processed anonymously. The processing of the data takes care of lifetime counters of the vehicle event data.
The data is being offered as an API. The API structure is equal to the rFMS standard being offered by OEM's like Scania, Volvo, Renault and DAF.
The understand the API a SDK documentation is available which offers real-time collection examples that is retrieved from the mock data.

# Dependencies :
- Ben Schaffer => OAuth2 library
- Vance Lucas => PHPDotEnv
# Note
This code is generated in with the use of Google Gemini 2.0, as a showcase to what extend a AI-agent can support the development. Short conclusion, a lot of specific feedback and guidance is needed, but it decreases the development time and code writing.

# prerequisite
The solution is purely build in PHP is free to use for anybody. 
In order to use this solution for personal use :
- install composer (https://getcomposer.org/download/) 
- install mariaDb or MySQL an make sure you created a 2 databases (https://mariadb.org/download/?t=mariadb&p=mariadb&r=11.7.2&os=windows&cpu=x86_64&pkg=msi&mirror=netcologne)
    -  1 for the OAuth2.0 authorisation and key storage
    -  1 for the mockdata
  This can be 2 different mariadb instances/servers
- I used Xampp to combine Apache and PHP > 8.0  (https://www.apachefriends.org/download.html)
  - I used a https (certificate) configuration and on request I can share the http-file to secure your application 
Create a new folder that is being hosted within the host direction from Apache.
In this folder create a adjusted .env file which refers to the created database tables/servers

 in the root of the application make sure the composer components are updated : 
    ```
    composer update
    ```

## Documentation

The docs [can be found in the `docs/` directory](docs/index.md) of this repository.

## License
See the [LICENSE](LICENSE) file for copyrights and limitations (MIT).

more information will follow.
Peter
