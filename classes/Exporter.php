<?php

use Illuminate\Support;
use LSS\Array2Xml;

require 'Player.php';
require 'View.php';

// retrieves & formats data from the database for export
class Exporter {
    public function __construct() {
    }

    function getPlayerStats($search) {

        $Player = new Player();

        $data = $Player->getStats($search);

        // calculate totals
        foreach ($data as &$row) {
            unset($row['player_id']);
            $row['total_points'] = ($row['3pt'] * 3) + ($row['2pt'] * 2) + $row['free_throws'];
            $row['field_goals_pct'] = $row['field_goals_attempted'] ? (round($row['field_goals'] / $row['field_goals_attempted'], 2) * 100) . '%' : 0;
            $row['3pt_pct'] = $row['3pt_attempted'] ? (round($row['3pt'] / $row['3pt_attempted'], 2) * 100) . '%' : 0;
            $row['2pt_pct'] = $row['2pt_attempted'] ? (round($row['2pt'] / $row['2pt_attempted'], 2) * 100) . '%' : 0;
            $row['free_throws_pct'] = $row['free_throws_attempted'] ? (round($row['free_throws'] / $row['free_throws_attempted'], 2) * 100) . '%' : 0;
            $row['total_rebounds'] = $row['offensive_rebounds'] + $row['defensive_rebounds'];
        }
        return collect($data);
    }

    function getPlayers($search) {
        
        $Player = new Player();

        return $Player->getAll($search);

    }

    public function format($data, $format = 'html') {
        
        // return the right data format
        switch($format) {
            case 'xml':
                header('Content-type: text/xml');
                
                // fix any keys starting with numbers
                $keyMap = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine'];
                $xmlData = [];
                foreach ($data->all() as $row) {
                    $xmlRow = [];
                    foreach ($row as $key => $value) {
                        $key = preg_replace_callback('(\d)', function($matches) use ($keyMap) {
                            return $keyMap[$matches[0]] . '_';
                        }, $key);
                        $xmlRow[$key] = $value;
                    }
                    $xmlData[] = $xmlRow;
                }
                $xml = Array2XML::createXML('data', [
                    'entry' => $xmlData
                ]);
                return $xml->saveXML();
                break;
            case 'json':
                header('Content-type: application/json');
                return json_encode($data->all());
                break;
            case 'csv':
                header('Content-type: text/csv');
                header('Content-Disposition: attachment; filename="export.csv";');
                if (!$data->count()) {
                    return;
                }
                $csv = [];
                
                // extract headings
                // replace underscores with space & ucfirst each word for a decent headings
                $headings = collect($data->get(0))->keys();
                $headings = $headings->map(function($item, $key) {
                    return collect(explode('_', $item))
                        ->map(function($item, $key) {
                            return ucfirst($item);
                        })
                        ->join(' ');
                });
                $csv[] = $headings->join(',');

                // format data
                foreach ($data as $dataRow) {
                    $csv[] = implode(',', array_values($dataRow));
                }
                return implode("\n", $csv);
                break;
            default: // html
                if (!$data->count()) {
                    return $this->htmlTemplate('Sorry, no matching data was found');
                }
                
                // extract headings
                // replace underscores with space & ucfirst each word for a decent heading
                $headings = collect($data->get(0))->keys();
                $headings = $headings->map(function($item, $key) {
                    return collect(explode('_', $item))
                        ->map(function($item, $key) {
                            return ucfirst($item);
                        })
                        ->join(' ');
                });
                $headings = '<tr><th>' . $headings->join('</th><th>') . '</th></tr>';

                // output data
                $rows = [];
                foreach ($data as $dataRow) {
                    $row = '<tr>';
                    foreach ($dataRow as $key => $value) {
                        $row .= '<td>' . $value . '</td>';
                    }
                    $row .= '</tr>';
                    $rows[] = $row;
                }
                $rows = implode('', $rows);
                return $this->htmlTemplate('<table>' . $headings . $rows . '</table>');
                break;
        }
    }

    // wrap html in a standard template
    public function htmlTemplate($html) {
		
		 $View = new View();
		 return $View->getHtml($html);
		 
         
    }
}

?>