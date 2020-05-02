<?php
require __DIR__.'/vendor/autoload.php';
use Carbon\Carbon;
use Tightenco\Collect\Support\Collection;

/**
 * 日付のコレクションオブジェクトを作成
 */
function makeDateArray($begin) : Collection{
  $begin = Carbon::parse($begin);
  $dates = [];
  while(true) {

    if ($begin->diffInDays(Carbon::now()) == 0) {
      break;
    } else {
      $dates[$begin->addDay()->format('Y-m-d').'T08:00:00.000Z'] =0;

    }
  }
  return new Collection($dates);
}

/**
 * Y/m/d H:i:sの日付をY/m/d H:iに変換
 */
function formatDate(string $date) :string
{
  // excelのシリアル値の場合の加工処理
  if (preg_match('#t(\d+)#', $date, $match)) {
    $date = formatSerialDate($match[1]);
  }

  if (preg_match('#(\d+/\d+/\d+)#', $date, $matches)) {
    $carbon = Carbon::parse($matches[1]);
    return $carbon->format('Y/m/d H:i');
  } else {
    throw new Exception('Can not parse date:'.$date);
  }

}

/**
 * excelのシリアル値を日付に変換する
 */
function formatSerialDate(string $serialDate) :string
{
  return date('Y/m/d H:i:s', ($serialDate - 25569) * 60 * 60 * 24);

}

/**
 * excelファイルの内容を連想配列に変換
 */
function xlsxToArray(string $path, string $sheet_name, string $range, $header_range = null)
{
  $reader = new PhpOffice\PhpSpreadsheet\Reader\Xlsx();

  $spreadsheet = $reader->load($path);

  $sheet = $spreadsheet->getSheetByName($sheet_name);
  $data =  new Collection($sheet->rangeToArray($range));
  $data = $data->map(function ($row) {
    return new Collection($row);
  });
  if ($header_range !== null) {
      $headers = xlsxToArray($path, $sheet_name, $header_range)[0];
      // TODO check same columns length
      return $data->map(function ($row) use($headers){
          return $row->mapWithKeys(function ($cell, $idx) use($headers){

            return [
              $headers[$idx] => $cell
            ];
        });
      });
  }

  return $data;
}

/**
 * 陽性患者のリストを作成
 */
function readPatients() : array
{
  $excelDir = __DIR__.'/downloads/patients.xlsx';
  $sheetName = 'Table 1';
  $data = xlsxToArray($excelDir, $sheetName, 'A3:H200', 'A2:H2');
  return [
    'date' => xlsxToArray($excelDir, $sheetName, 'H1')[0][0], // データ更新日
    'data' => $data->filter(function ($row) {
      return $row['発表日'];
    })->map(function ($row) {
      $date = formatDate($row['発表日']);
      $carbon = Carbon::parse($date);
      $row['発表日'] = $carbon->format('Y-m-d').'T08:00:00.000Z';
      $row['date'] = $carbon->format('Y-m-d');
      $row['w'] = $carbon->format('w');
      $row['short_date'] = $carbon->format('m/d');
      $row['start_of_week'] = $carbon->startOfWeek()->format('Y-m-d');
      return $row;
    })
  ];
}

/**
 * 日付ごとの小計リストに変換
 */
function createSummary(array $patients) {
  $dates = makeDateArray('2020-01-23');

  return [
    'date' => $patients['date'],
    'data' => $dates->map(function ($val, $key) {
      return [
        '日付' => $key,
        '小計' => $val
      ];
    })->merge($patients['data']->groupBy('start_of_week')->map(function ($group, $key) {
      return [
        '日付' => $key,
        '小計' => $group->count()
      ];
    }))->values()
  ];

}

$patients = readPatients();
$patients_summary = createSummary($patients);

$data = compact([
  'patients',
  'patients_summary',
]);
$lastUpdate = '';

$lastTime = 0;
foreach ($data as $key => &$arr) {
    if ($arr['date'] == null) {
      continue;
    }
    $arr['date'] = formatDate($arr['date']);
    $timestamp = Carbon::parse()->format('YmdHis');
    if ($lastTime <= $timestamp) {
      $lastTime = $timestamp;
      $lastUpdate = Carbon::parse($arr['date'])->addDay()->format('Y/m/d 22:00');
    }
}
$data['lastUpdate'] = $lastUpdate;

$data['main_summary'] = [
  'attr' => '検査実施人数',
  'value' => xlsxToArray(__DIR__.'/downloads/summary.xlsx', '検査実施サマリ', 'A2')[0][0],
  'children' => [
    [
      'attr' => '陽性患者数',
      'value' => $better_patients_summary['data']['感染者数']->sum(),
      'children' => [
        [
          'attr' => '入院中',
          'value' => $better_patients_summary['data']['感染者数']->sum() - $better_patients_summary['data']['退院者数']->sum() - $better_patients_summary['data']['死亡者数']->sum(),
          'children' => [
            [
              'attr' => '軽症・中等症',
              'value' => $better_patients_summary['data']['軽症']->sum() + $better_patients_summary['data']['中等症']->sum()
            ],
            [
              'attr' => '重症',
              'value' => $better_patients_summary['data']['重症']->sum()
            ]
          ]
        ],
        [
          'attr' => '退院',
          'value' => $better_patients_summary['data']['退院者数']->sum()
        ],
        [
          'attr' => '死亡',
          'value' => $better_patients_summary['data']['死亡者数']->sum()
        ]

      ]
    ]
  ]
];

file_put_contents(__DIR__.'/../data/data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
