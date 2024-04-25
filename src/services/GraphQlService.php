<?php

namespace digitaldiff\et\services;

use Craft;
use craft\base\Component;
use digitaldiff\et\models\FetchDataModel;
use GuzzleHttp\Client;

class GraphQlService extends Component
{
    public function newClient($query, $variables = [])
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . getenv('BEARER_TOKEN')
            ]
        ]);
        $response = $client->post(getenv('API_URL'), [
            'body' => json_encode([
                'query' => $query,
                'variables' => $variables
            ])
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function getEntryId()
    {
        $query = 'query getEntry {
          websitesEntries(href: "' . Craft::$app->getSites()->getPrimarySite()->baseUrl . '") {
            ... on websites_default_Entry {
              id
            }
          }
        }';

        $data = $this->newClient($query);
        $count = count($data['data']['websitesEntries']);
        if ($count > 0) {
            return $data['data']['websitesEntries'][0]['id'];
        } else {
            return 0;
        }

    }

    public function newEntry()
    {
        $query = 'mutation createWebsite($authorId: ID, $href: String, $version: String) {
          save_websites_default_Entry(authorId: $authorId, href: $href, version: $version, siteId: 10) {
            ... on websites_default_Entry {
                href,
                version
            }
          }
        }';

        $fetchDataModel = new FetchDataModel(); // Create an instance of FetchDataModel
        $fetchDataModel= $fetchDataModel->getTableRows('craft_info'); // Call the getTableRows method
        $version = $fetchDataModel[0]['version'];

        $variables = [
            "authorId" => 1,
            "href" => Craft::$app->getSites()->getPrimarySite()->baseUrl,
            "version" => $version,
            "plugins" => true
        ];
        return $this->newClient($query, $variables);
    }

    public function updateEntry()
    {
        $query = 'mutation updateWebsite($entryId: ID, $plugins: [plugins_MatrixBlockContainerInput], $sortOrder: [QueryArgument], $authorId: ID, $href: String, $version: String) {
          save_websites_default_Entry(
            id: $entryId
            plugins: {blocks: $plugins, sortOrder: $sortOrder}
            authorId: $authorId
            href: $href
            version: $version
            siteId: 10
          ) {
            ... on websites_default_Entry {
              id
              href
              version
              plugins {
                ... on plugins_plugin_BlockType {
                  pluginName
                  pluginVersion
                }
              }
            }
          }
        }';
        $fetchDataModel = new FetchDataModel(); // Create an instance of FetchDataModel
        $fetchDataModel2 = $fetchDataModel->getTableRows('craft_info'); // Call the getTableRows method
        $version = $fetchDataModel2[0]['version'];

        $plugins = $fetchDataModel->getTableRows('craft_plugins');

        $plugins2 = array();
        for ($i = 0; $i < count($plugins); $i++) {
            $plugins2[$i] = [
                "plugin" => [
                    "id" => "new".$i+1,
                    "pluginName" => $plugins[$i]['handle'],
                    "pluginVersion" => $plugins[$i]['version']
                ]
            ];
        }

        $new = array();
        for ($i = 0; $i < count($plugins); $i++) {
            $new[$i] = "new".$i+1;
        }

        $variables = [
            "entryId" => $this->getEntryId(),
            "authorId" => 1,
            "href" => Craft::$app->getSites()->getPrimarySite()->baseUrl,
            "version" => $version,
            "sortOrder" => $new,
            "plugins" => $plugins2
        ];

        return $this->newClient($query, $variables);
    }
}