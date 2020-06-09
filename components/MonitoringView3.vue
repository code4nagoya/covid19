<template>
  <data-view
    title="判断指標と達成状況3"
    title-id="new-patients-chart"
    url="https://www.pref.aichi.jp/site/covid19-aichi/"
    :date="updateAt"
    :remarks="remarks"
  >
    <template v-slot:button>
      <data-selector v-model="dataKind" :items="dataKinds" />
    </template>

    <time-bar-line-chart
      v-if="dataKind === 'history'"
      chart-id="monitoring-chart"
      :chart-data="chartData"
      :y-axis-left-setting="yAxisLeftSetting"
      legend-order-kind="asc"
    />

    <table v-if="dataKind === 'latest'" class="table">
      <thead>
        <tr>
          <th scope="col" class="col-header">
            基準項目
          </th>
          <th
            scope="col"
            class="col-header"
            :bgcolor="indicator.colors.caution.bgColor"
            :style="'color: ' + indicator.colors.caution.textColor"
          >
            注意<br />領域
          </th>
          <th
            scope="col"
            class="col-header"
            :bgcolor="indicator.colors.danger.bgColor"
            :style="'color: ' + indicator.colors.danger.textColor"
          >
            危険<br />領域
          </th>
          <th
            scope="col"
            class="col-header"
            :bgcolor="totalCell.bgColor"
            :style="'color: ' + totalCell.textColor"
          >
            {{ displayDate }}<br />の状況
          </th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th scope="row" class="row-header">
            新規感染者数
          </th>
          <td class="col-caution">{{ indicator.patients.caution }}人</td>
          <td class="col-danger">{{ indicator.patients.danger }}人</td>
          <td
            class="col-actual"
            :bgcolor="patientCell.bgColor"
            :style="'color: ' + patientCell.textColor"
          >
            {{ patientCell.label }}人
          </td>
        </tr>
        <tr>
          <th scope="row" class="row-header">
            陽性率
          </th>
          <td class="col-caution">{{ indicator.rate.caution }}%</td>
          <td class="col-danger">{{ indicator.rate.danger }}%</td>
          <td
            class="col-actual"
            :bgcolor="rateCell.bgColor"
            :style="'color: ' + rateCell.textColor"
          >
            {{ rateCell.label }}%
          </td>
        </tr>
        <tr style="border: 3px solid #a83945; border-top-style:none;">
          <th scope="row" class="row-header">
            入院患者数
          </th>
          <td class="col-caution">{{ indicator.hospitals.caution }}人</td>
          <td class="col-danger">{{ indicator.hospitals.danger }}人</td>
          <td
            class="col-actual"
            :bgcolor="hospitalCell.bgColor"
            :style="'color: ' + hospitalCell.textColor"
          >
            {{ hospitalCell.label }}人
          </td>
        </tr>
        <tr style="height: 20px" />
        <tr style="border: 3px solid #a83945">
          <th scope="row" class="row-header">
            感染率
          </th>
          <td class="col-caution">0.5人</td>
          <td class="col-danger" />
          <td class="col-actual">{{ youseiritsuCell.label }}人</td>
        </tr>
      </tbody>
    </table>

    <div>
      <ul class="remarks">
        <!-- eslint-disable vue/no-v-html -->
        <li
          v-for="remarks_text in remarks"
          :key="remarks_text"
          v-sanitaize
          v-html="remarks_text"
        />
        <!-- eslint-disable vue/no-v-html -->
      </ul>
    </div>

    <template v-slot:infoPanel>
      <data-view-basic-info-panel
        l-title="判定"
        :l-text="displayInfo.lText"
        :s-text="displayInfo.sText"
        :unit="displayInfo.unit"
      />
    </template>
  </data-view>
</template>

<style lang="scss" scoped>
ul.remarks {
  font-size: 0.75rem;
  list-style-type: '※ ';
}

.table {
  height: 0;
  width: 100%;
  color: #a83945;
  border-collapse: collapse;
  border: 3px solid #a83945;
  font-size: 1.2rem;
  margin-bottom: 20px;
}

.col-header {
  padding: 10px;
  border: 1px solid #a83945;
}

.row-header {
  text-align: left;
  padding: 10px;
  border: 1px solid #a83945;
}

.col-caution {
  text-align: center;
  border: 1px solid #a83945;
}

.col-danger {
  text-align: center;
  border: 1px solid #a83945;
}

.col-actual {
  text-align: center;
  border: 1px solid #a83945;
}
</style>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator'
import dayjs, { Dayjs } from 'dayjs'
import * as Enumerable from 'linq'
import NewPatientsChart, {
  NewPatientsAverageType
} from './NewPatientsChart.vue'
import InspectionPersonsChart, {
  InspectionPersonAverageType
} from './InspectionPersonsChart.vue'
import HospitalizedChart, {
  HospitalizedAverageType
} from './HospitalizedChart.vue'
import {
  MainSummaryDataType,
  PatientsSummaryDaily,
  InspectionPersonsSummaryDaily
} from '~/utils/types'
import DataView from '@/components/DataView.vue'
import DataSelector, { SelectorItem } from '@/components/DataSelector.vue'
import TimeBarLineChart, {
  GraphData,
  YAxisSetting
} from '@/components/TimeBarLineChart.vue'
import DataViewBasicInfoPanel from '@/components/DataViewBasicInfoPanel.vue'
import { formatNumber } from '@/utils/formatNumber'

export const AICHI_POPULATION = 7549422

type ScoreType = {
  date: Dayjs
  patients7DaysNum: number | undefined // 新規感染者数(7日間平均)
  patients7DaysScore: number | undefined // 新規感染者数の危険度(100=20人)
  patients10M7DaysNum: number | undefined // 感染率(7日間の10万人あたり感染者数)
  patients10M7DaysScore: number | undefined // 感染率の危険度(50=0.5人)
  positivesRate: number | undefined // 陽性率(7日分の陽性者数÷検査人数)
  positivesScore: number | undefined // 陽性率の危険度(100=10%)
  hospitals7DaysNum: number | undefined // 入院患者数(7日間平均)
  hospitals7DaysScore: number | undefined // 入院患者数の危険度(50=150人, 100=250人)
}

type DataKind = 'latest' | 'history'

type DisplayInfo = {
  lText: string
  sText: string
  unit: string
}

type StatusKind = 0 | 1 | 2 // 0:基準値未満, 1:注意, 2:危険

type Colors = {
  bgColor: string
  textColor: string
}

type Values = {
  caution: number
  danger: number
}

type CellInfo = Colors & {
  label: string
  status: StatusKind
}

@Component({
  components: {
    DataView,
    DataSelector,
    TimeBarLineChart,
    DataViewBasicInfoPanel
  }
})
export default class MonitoringView3 extends Vue {
  private readonly indicator = {
    colors: {
      caution: {
        bgColor: '#f0e68c',
        textColor: 'a83945'
      } as Colors,
      danger: {
        bgColor: '#ff6347',
        textColor: 'white'
      } as Colors
    },
    patients: {
      caution: 10,
      danger: 20
    } as Values,
    rate: {
      caution: 5.0,
      danger: 10.0
    } as Values,
    hospitals: {
      caution: 150,
      danger: 250
    } as Values
  }

  private readonly remarks = [
    '<a class=RemarksLink target=_blank href=https://www.pref.aichi.jp/site/covid19-aichi/taisakusisin.html>愛知県新型コロナウイルス感染拡大予防対策指針</a>の「指標」に基き、当サイトが独自に状況を掲載するもので、愛知県の公式発表ではありません',
    '「新規感染者数」「入院患者数」は、過去7日間の平均です。定義詳細は「新規感染者数」「入院患者数」の各グラフを参照',
    '「陽性率」は参考値の場合があります。定義詳細は「陽性率・検査実施人数」のグラフを参照',
    '「感染率」とは、直近1週間の10万人あたり感染者数です。その算出には、推計人口(2020年3月1日時点)を使用'
  ]

  @Prop()
  public parientsDate!: string

  @Prop()
  public parientsData!: PatientsSummaryDaily[]

  @Prop()
  public inspectionPersonsDate!: string

  @Prop()
  public inspectionPersonsData!: InspectionPersonsSummaryDaily[]

  @Prop()
  public mainSummaryDate!: string

  @Prop()
  public mainSummaryData!: MainSummaryDataType[]

  private updateAt = '-'
  private displayDate = '-'
  private patientCell: CellInfo
  private rateCell: CellInfo
  private hospitalCell: CellInfo
  private totalCell: CellInfo
  private youseiritsuCell: CellInfo

  private dataKind: DataKind = 'latest'
  private readonly dataKinds = [
    { key: 'latest', label: '最新' } as SelectorItem,
    { key: 'history', label: '推移' } as SelectorItem
  ]

  private readonly chartDataSet = new Map<DataKind, GraphData>()

  private readonly yAxisLeftSetting: YAxisSetting = {
    min: 0,
    suggestedMax: 110,
    unit: '%',
    visible: true,
    stacked: false,
    visibleAxisValue: false
  } as YAxisSetting

  private get displayInfo(): DisplayInfo {
    let lText = '－'
    switch (this.totalCell.status) {
      case 1:
        lText = '注意'
        break
      case 2:
        lText = '危険'
        break
    }

    return {
      lText,
      sText: `${this.displayDate} 時点`,
      unit: ''
    }
  }

  private readonly formatDateLabel = (date: string | string[]): string => {
    return (typeof date === 'string' ? [date] : date)
      .map(d => dayjs(d).format('M/D'))
      .join('～')
  }

  constructor() {
    super()

    const scores = this.build7daysScore()

    // 3つのデータのもっとも最新の更新日をこのパネルの更新日とする
    this.updateAt = Enumerable.from([
      this.parientsDate,
      this.inspectionPersonsDate,
      this.mainSummaryDate
    ]).maxBy(d => d)

    // 3つのデータそれぞれの最新日付の中で、最も古い日を現状日とする
    const latestMinDate = scores.last().date
    this.displayDate = latestMinDate.format('M/D')

    const emptyCell = {} as CellInfo
    this.patientCell = Object.assign({}, emptyCell) as CellInfo
    this.rateCell = Object.assign({}, emptyCell) as CellInfo
    this.hospitalCell = Object.assign({}, emptyCell) as CellInfo

    const applyStyle = (
      average: number | undefined,
      indicators: Values,
      cautionColors: Colors,
      dangerColors: Colors,
      cell: CellInfo
    ) => {
      if (average != null) {
        cell.label = formatNumber(average)
        if (average >= indicators.danger) {
          cell.bgColor = dangerColors.bgColor
          cell.textColor = dangerColors.textColor
          cell.status = 2
        } else if (average >= indicators.caution) {
          cell.bgColor = cautionColors.bgColor
          cell.textColor = cautionColors.textColor
          cell.status = 1
        }
      }
    }

    const latest = scores.last()

    const items = Enumerable.from([
      {
        value: latest.patients7DaysNum,
        indicator: this.indicator.patients,
        cell: this.patientCell
      },
      {
        value: latest.positivesRate,
        indicator: this.indicator.rate,
        cell: this.rateCell
      },
      {
        value: latest.hospitals7DaysNum,
        indicator: this.indicator.hospitals,
        cell: this.hospitalCell
      }
    ])

    items.forEach(item =>
      applyStyle(
        item.value,
        item.indicator,
        this.indicator.colors.caution,
        this.indicator.colors.danger,
        item.cell
      )
    )

    this.totalCell = Object.assign({}, emptyCell) as CellInfo
    if (items.all(d => d.cell.status === 2)) {
      // 全て危険ならその日は赤
      this.totalCell.bgColor = this.indicator.colors.danger.bgColor
      this.totalCell.textColor = this.indicator.colors.danger.textColor
      this.totalCell.status = 2
    } else if (items.any(d => d.cell.status >= 1)) {
      // 一つでも注意以上ならその日は黄色
      this.totalCell.bgColor = this.indicator.colors.caution.bgColor
      this.totalCell.textColor = this.indicator.colors.caution.textColor
      this.totalCell.status = 1
    }

    this.youseiritsuCell = Object.assign({}, emptyCell) as CellInfo
    this.youseiritsuCell.label =
      latest.patients10M7DaysNum == null
        ? '-'
        : formatNumber(latest.patients10M7DaysNum, 2)

    const graphData = this.buildDailyTransitionGraphData(scores)
    this.chartDataSet.set('history', graphData)
  }

  private get chartData(): GraphData {
    const data = this.chartDataSet.get(this.dataKind)
    if (data) {
      return data
    } else {
      return {
        dates: [],
        datasets: []
      } as GraphData
    }
  }

  private build7daysScore = (): Enumerable.IEnumerable<ScoreType> => {
    // 新規感染者数
    const newPatients = NewPatientsChart.makeAverageNewPatients(
      this.parientsData ?? []
    )
    // 陽性率
    const positives = InspectionPersonsChart.makeAveragePositives(
      this.inspectionPersonsData ?? []
    )
    // 入院患者数
    const hospitals = HospitalizedChart.makeAverageHospitals(
      this.mainSummaryData ?? []
    )

    // 3つのデータそれぞれの最古日付の中で、最も新しい日をグラフの開始日とする
    const startDate = Enumerable.from([
      dayjs(newPatients.first().date),
      dayjs(positives.first().date),
      dayjs(hospitals.first().date)
    ]).maxBy(x => x)

    // 3つのデータそれぞれの最新日付の中で、最も古い日をグラフの終了日とする
    const endDate = Enumerable.from([
      dayjs(newPatients.last().date),
      dayjs(positives.last().date),
      dayjs(hospitals.last().date)
    ]).minBy(x => x)

    const now = dayjs()

    return newPatients
      .where(d => startDate <= d.date && d.date <= endDate)
      .zip<ScoreType>(
        positives.where(d => startDate <= d.date && d.date <= endDate),
        hospitals.where(d => startDate <= d.date && d.date <= endDate),
        (
          n: NewPatientsAverageType,
          p: InspectionPersonAverageType,
          h: HospitalizedAverageType
        ): any => {
          return {
            date: n.date,
            patients7DaysNum: n.average7days, // 新規感染者数(7日間平均)
            patients7DaysScore:
              n.average7days == null ? undefined : (n.average7days / 20) * 100, // 新規感染者数の危険度(100=20人)
            patients10M7DaysNum:
              n.count7days == null
                ? undefined
                : (n.count7days / AICHI_POPULATION) * 100000, // 感染率(7日間の10万人あたり感染者数)
            patients10M7DaysScore:
              n.count7days == null
                ? undefined
                : (n.count7days / AICHI_POPULATION) * 100000 * 100, // 感染率の危険度(50=0.5人)
            positivesRate: p.average, // 陽性率(7日分の陽性者数÷検査人数)
            positivesScore:
              p.average == null ? undefined : (p.average / 10) * 100, // 陽性率の危険度(100=10%)
            hospitals7DaysNum: h.average, // 入院患者数(7日間平均)
            //  // 入院患者数の危険度(50=150人, 100=250人)
            hospitals7DaysScore:
              h.average == null
                ? undefined
                : (h.average ?? 0) - 50 <= 0
                ? 0
                : (((h.average ?? 0) - 50) / 100) * 50
          } as ScoreType
        }
      )
      .where(d => d.date < now)
  }

  private buildDailyTransitionGraphData = (
    rows: Enumerable.IEnumerable<ScoreType>
  ): GraphData => {
    return {
      dates: rows.select(d => d.date.format('YYYY-MM-DD')).toArray(),
      datasets: [
        {
          type: 'line',
          title: '新規感染者数',
          unit: '人',
          values: rows.select(d => d.patients7DaysScore).toArray(),
          tooltipTexts: rows
            .select(d => `新規感染者数: ${formatNumber(d.patients7DaysNum)} 人`)
            .toArray(),
          order: 1,
          color: '#bd3f4c'
        },
        {
          type: 'line',
          title: '陽性率',
          unit: '%',
          values: rows.select(d => d.positivesScore).toArray(),
          tooltipTexts: rows
            .select(d => `陽性率: ${formatNumber(d.positivesRate)} %`)
            .toArray(),
          order: 2,
          color: '#0070C0'
        },
        {
          type: 'line',
          title: '入院患者数',
          unit: '人',
          values: rows.select(d => d.hospitals7DaysScore).toArray(),
          tooltipTexts: rows
            .select(d => `入院患者数: ${formatNumber(d.hospitals7DaysNum)} 人`)
            .toArray(),
          order: 3,
          color: '#92D050'
        },
        {
          type: 'line',
          title: '感染率',
          unit: '人',
          values: rows.select(d => d.patients10M7DaysScore).toArray(),
          tooltipTexts: rows
            .select(d => `感染率: ${formatNumber(d.patients10M7DaysNum, 2)} 人`)
            .toArray(),
          order: 4,
          color: '#7F7F7F'
        },
        {
          type: 'line',
          title: '注意領域',
          unit: '%',
          values: rows.select(_ => 50).toArray(),
          tooltipVisible: false,
          order: 101,
          color: '#f0e68c',
          lineStyle: 'dashed'
        },
        {
          type: 'line',
          title: '危険領域',
          unit: '%',
          values: rows.select(_ => 100).toArray(),
          tooltipVisible: false,
          order: 102,
          color: '#ff6347',
          lineStyle: 'dashed'
        }
      ]
    } as GraphData
  }
}
</script>
