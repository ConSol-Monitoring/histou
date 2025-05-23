# Histou

Adds templates to Grafana in combination with [Nagflux](https://github.com/ConSol-Monitoring/nagflux).

Histou is designed to add templates to Grafana from Nagios/Naemon data. Therefor Nagflux sends the informations from Naemon/Nagios/Icinga(2) to an InfluxDB. On the otherhand, Grafana is used to display this performancedata. Histou adds a custom dashboard to Grafana which will display dynamic dashboards, depending on the given host and service name in the URL. Histou fetches first additional informations from the InfluxDB and select afterwards a template. Templates can be JSON or PHP Files which contains a Grafana dashboard, the have also a rule, which defines when this template is used.

## Installation

### Dependencies

- Webserver with PHP 5.3.3+
- PHP commandline tool / phpcgi

### Webserver

- The whole Histoufolder accessible by copying to your webserver. On Debian with apache this would mean, to copy the whole Histoufolder to /var/www/

### Grafana

- Move the file `histou.js` in the `public/dashboards/` folder within Grafana.
- Depending on the URL the `index.php` is published on the webserver, change the Variable `var url = 'http://localhost/histou/';` in in the histou.js file. If you copied the Histoufolder to the root of your webserver, the default URL is perfect.

### OMD - the easy way

Nagflux is fully integrated in [OMD-Labs](https://github.com/ConSol-Monitoring/omd), as well as Histou is. Therefor if you wanna try it out, it's maybe easier to install OMD-Labs.

## Configuration

### Configfile

Here are some of the important config-options:

| Section       | Config-Key    | Meaning       |
| ------------- | ------------- | ------------- |
|general|socketTimeout|This timeout defines the duration in seconds, which Histou will wait for a response from the InfluxDB|
|general|phpCommand|This is the command which is used to call PHP on the commandline. If it's in the PATH php is mostly enough, if not write the full path to the file.|
|general|tmpFolder|Set a folder path, if the default PHP Tmp folder does not suite you.|
|general|specialChar|Can be used to create more specific regex within the rules. E.g. $host = '&host&' will be replaced with 'linux-server1' if the select hostname is linux-server1. This works likewise with host, service, command.|
|general|databaseType|Choose between influxDB and victoriametrics. |
|general|datasourceName|Choose different datasourcename for victoriametrics backend|
|general|disablePanelTitle|If this is set to true the PanelTitels are hidden globaly, there is an URL Flag which does it just with the current page. It is usefull to get a bigger Graphpicture.|
|folder|defaultTemplateFolder|This is the path to the folder containing the default templates|
|folder|customTemplateFolder|This is the path to the folder containing the custom templates. The templates in this folder will override files in the default folder, if they have the same filename|
|influxdb|url|You can guess it...|
|influxdb|hostcheckAlias|This string will be used on hostchecks, because normaly there is no service name for it, but Histou needs one.|

### URL-Parameters

They are just valid for the current call, you can't change anything permanently.

| Name          | Type          | Meaning       |
| ------------- | ------------- | ------------- |
|host|String|The hostname to generate the graph for|
|service|String|The servicename to generate the graph for|
|height|String|The height of an panel, e.g. default is: "400px"|
|legend|Bool|If false the legend will be hidden, default: true|
|annotations|Bool|If false the annotations will be unchecked|
|debug|Flag|If set an additional Panel will be added to the bottom which contains some debug infos|
|disablePanelTitle|Flag|If set the Paneltitel will be hidden|
|specificTemplate|String|If a filename is passed this away, only this template will be used, regardless the rules. E.g. ping.simple. Makes more or less just sens with simple templates|
|disablePerfdataLookup|Flag|If set no data will be fetched from the InfluxDB, so the only data available in the template is the host and servicename. Works only if specificTemplate is set!|
|customCSSFile|String|You could give the address of an CSS file which will be loaded into the Grafana context. Here an example: ![Custom CSS Example](custom_grafana.css "Custom CSS Example")|
|reduce|Flag|If set distracting elements of a graph will be hidden, used for Thruk.|

## Templates

There are two types of templates, the simple and the PHP. Simple templates are static, PHP are dynamic. Both cointains a rule, which describes on which host/service combination the template is uses. The type of the template is defined by its file-ending, `.simple` or `.php`

### Rule

A rule contains four keys: host, service, command and perfLabel. The values describes when the template is used. You can write for example an fixed hostname in the host field if the template should be uses just for one Host. It is also possible to use Regular Expressions in every field. Rules have a hierarchy on the to there is the host on the bottom the perfLabel. If a rule does not match in the hostname the perfLabel beneath will not be checked.

#### Typical Rules

- One rule for all ping checks, due to the fact that every check returns an pl and rta as perfLabel you can match on them
  - host: .*
  - service: .*
  - command: .*
  - perfLabel: rta, pl
- ping template just for the test systems
  - host: test-.*
  - service: .*
  - command: .*
  - perfLabel: rta, pl

### Simple

Simple templates contain a rule followed by an JSON object. The reason for this kind of template is, to over the user an easy way to create dashboards. The tradeoff is, that in the simple template it is not possible to use a template created for service1 to use on service2 if the perfLabels differ between those services.

### PHP

In the PHP template you can write PHP code which will be executed when the template gets chosen. You just have to return a JSON string or an object of the build-in PHP dashboard.

## Presentation

Here is a presentation about Nagflux and Histou on [www.slideshare.net](http://www.slideshare.net/PhilipGriesbacher/monitoring-workshop-kiel-2016-performancedaten-visualisierung-mit-grafana-influxdb) (only in German, sorry)
