# MockServer
As being part of an eco-system to showcase the abilities of vehicle event data provided by European OEM truck manufactures. The eco system consists of following components:
- rFMS Collector ( back-end scheduler to retrieve API-data from various interface like rFMS, RDW and OpenWeather.)
- rFMS Connect ( Front-end for the FleetManagement system -> https://rfmsconnect.nl or https://demo.rfmsconnect.nl)

This solution provides mocked data based on real vehicle event data. The real data will be processed anonymously. The processing of the data takes care of lifetime counters of the vehicle event data.

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
