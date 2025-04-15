# MockServer
As being part of an eco-system to showcase the abilities of vehicle event data provided by European OEM truck manufactures. The eco system consists of following components:
- rFMS Collector ( back-end scheduler to retrieve API-data from various interface like rFMS, RDW and OpenWeather.)
- rFMS Connect ( Front-end for the FleetManagement system -> https://rfmsconnect.nl or https://demo.rfmsconnect.nl)

This solution provides mocked data based on real vehicle event data. The real data will be processed anonymously. The processing of the data takes care of lifetime counters of the vehicle event data.
The data is being offered as an API. The API structure is equal to the rFMS standard being offered by OEM's like Scania, Volvo, Renault and DAF.
The understand the API a SDK documentation is available which offers real-time collection examples that is retrieved from the mock data.

Dependencies :
- Ben Schaffer => OAuth2 library
- Vance Lucas => PHPDotEnv

This code is generated in with the use of Google Gemini 2.0, as a showcase to what extend a AI-agent can support the development. Short conclusion, a lot of specific feedback and guidance is needed, but it decreases the development time and code writing.

The solution is purely build in PHP is free to use for anybody. 
In order to use this solution for personal use :
- install composer
- install mariaDb or MySQL an make sure you created a 2 databases
    -  1 for the OAuth2.0 authorisation and key storage
    -  1 for the mockdata
  This can be 2 different mariadb instances/servers
- I used Xampp to combine Apache and PHP > 8.0
Create a new folder that is being hsoted within the host direction from Apache.
In this folder create a adjusted .env file which refers to the created database tables/servers

 in the root of the application make sure the composer components are updated : composer update



more information will follow.
Peter
