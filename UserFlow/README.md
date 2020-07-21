# User Flow Plugin

This plugin was built using the amCharts 4 library for Matomo v4.0.0-b2. 

## Description

Analyzes the workflow of users of a website tracked by Matomo.  
Provides metrics such as the number of visitors on the website, 
their average time spent on a particular URL and the subsequent URL
which they have moved on to.

![UI Example](./images/UI.png)

## Installation

To install Matomo, visit the installation guide [here](https://matomo.org/docs/installation/).

To add to this plugin Matomo, add the entire repository to matomo/plugins folder.  
Activate the plugin from Settings > Plugins or type
`./console plugin:activate matomo-userflow` in the root folder of Matomo.

## Specifications

The plugin current supports tracking for the last 100 visitors and records up to 50 actions
performed by the visitors. 
