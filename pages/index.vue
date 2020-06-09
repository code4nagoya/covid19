<template>
  <div class="MainPage">
    <page-header
      :icon="headerItem.icon"
      :title="headerItem.title"
      :date="headerItem.date"
    />
    <whats-new
      class="mb-1"
      :text="newsItem.text"
      :date="newsItem.date"
      :url="newsItem.url"
    />
    <whats-new-about
      class="mb-4"
      text="当サイトは有志が作成したものです。お問い合わせは愛知県ではなく、当サイト運営まで。"
      url="/about"
    />
    <v-row class="DataBlock">
      <v-col cols="12" md="6" class="DataCard">
        <monitoring-view
          :parients-date="Data.patients_summary.date"
          :parients-data="Data.patients_summary.data"
          :inspection-persons-date="Data.inspection_persons_summary.date"
          :inspection-persons-data="Data.inspection_persons_summary.data"
          :main-summary-date="Data.main_summary_history.date"
          :main-summary-data="Data.main_summary_history.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <monitoring-view-2
          :parients-date="Data.patients_summary.date"
          :parients-data="Data.patients_summary.data"
          :inspection-persons-date="Data.inspection_persons_summary.date"
          :inspection-persons-data="Data.inspection_persons_summary.data"
          :main-summary-date="Data.main_summary_history.date"
          :main-summary-data="Data.main_summary_history.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <monitoring-view-3
          :parients-date="Data.patients_summary.date"
          :parients-data="Data.patients_summary.data"
          :inspection-persons-date="Data.inspection_persons_summary.date"
          :inspection-persons-data="Data.inspection_persons_summary.data"
          :main-summary-date="Data.main_summary_history.date"
          :main-summary-data="Data.main_summary_history.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <new-patients-chart
          :date="Data.patients_summary.date"
          :daily-data="Data.patients_summary.data"
          :weekly-data="dataWeekly.patients_summary.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <inspection-persons-chart
          :date="Data.inspection_persons_summary.date"
          :daily-data="Data.inspection_persons_summary.data"
          :weekly-data="dataWeekly.inspection_persons_summary.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <hospitalized-chart
          :date="Data.main_summary_history.date"
          :daily-data="Data.main_summary_history.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <main-summary-chart
          :date="Data.main_summary_history.date"
          :daily-data="Data.main_summary_history.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <svg-card
          title="検査陽性者の状況"
          :title-id="'details-of-confirmed-cases'"
          :date="headerItem.date"
          :url="'https://www.pref.aichi.jp/site/covid19-aichi/'"
          :subtext="'（一部、県知事のTwitter）'"
          :title-date="confirmedCases['更新日時']"
          :title-remark="confirmedCases['備考']"
        >
          <confirmed-cases-table v-bind="confirmedCases" />
        </svg-card>
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <column-map
          title="市町村別感染者数"
          :title-id="'patients-per-cities'"
          :date="Data.patients.date"
          :data="patientsPerCities"
          :legends="patientsPerCitiesLegends"
          :title-date="Data.patients.date"
          :remarks="[
            '公表されている居住地が、54市町村以外(尾張地方、三河地方など)や県外は対象外',
            '感染率とは10万人あたり感染者数を指し、その算出には、推計人口(2020年3月1日時点)を使用'
          ]"
          :url="'https://www.pref.aichi.jp/site/covid19-aichi/'"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <critically-chart
          :date="Data.main_summary_history.date"
          :daily-data="Data.main_summary_history.data"
        />
      </v-col>

      <v-col cols="12" md="6" class="DataCard">
        <inspection-count-chart
          :date="Data.inspections_summary.date"
          :daily-data="Data.inspections_summary.data"
          :weekly-data="dataWeekly.inspections_summary.data"
        />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import PageHeader from '@/components/PageHeader.vue'
import WhatsNew from '@/components/WhatsNew.vue'
import WhatsNewAbout from '@/components/WhatsNewAbout.vue'
import Data from '@/data/data.json'
import CityData from '@/data/city_data.json'

import formatConfirmedCases from '@/utils/formatConfirmedCases'
import formatPatientsPerCities from '@/utils/formatPatientsPerCities'
import SvgCard from '@/components/SvgCard.vue'
import ConfirmedCasesTable from '@/components/ConfirmedCasesTable.vue'
import ColumnMap from '@/components/ColumnMap.vue'
import NewPatientsChart from '@/components/NewPatientsChart.vue'
import InspectionCountChart from '@/components/InspectionCountChart.vue'
import InspectionPersonsChart from '@/components/InspectionPersonsChart.vue'
import MainSummaryChart from '@/components/MainSummaryChart.vue'
import HospitalizedChart from '@/components/HospitalizedChart.vue'
import CriticallyChart from '@/components/CriticallyChart.vue'
import MonitoringView from '@/components/MonitoringView.vue'
import MonitoringView2 from '@/components/MonitoringView2.vue'
import MonitoringView3 from '@/components/MonitoringView3.vue'
import weeklizer from '@/utils/weeklizer'
import normalizer from '@/utils/normalizer'

export default {
  components: {
    PageHeader,
    WhatsNew,
    WhatsNewAbout,
    SvgCard,
    ConfirmedCasesTable,
    ColumnMap,
    NewPatientsChart,
    MainSummaryChart,
    HospitalizedChart,
    CriticallyChart,
    InspectionCountChart,
    InspectionPersonsChart,
    MonitoringView,
    MonitoringView2,
    MonitoringView3
  },
  data() {
    // 日次データの補正
    normalizer(Data)

    // 日次データを週次化
    const dataWeekly = weeklizer(Data) // Data_weekly.json 化までのつなぎ

    // 検査陽性者の状況
    const confirmedCases = formatConfirmedCases(Data.main_summary_history)

    // 市区町村別の感染者数
    const patientsPerCitiesLegends = []
    const patientsPerCities = formatPatientsPerCities(
      CityData.data,
      Data.patients.data,
      patientsPerCitiesLegends
    )

    const data = {
      Data,
      dataWeekly,
      confirmedCases,
      patientsPerCities,
      patientsPerCitiesLegends,
      headerItem: {
        icon: 'mdi-chart-timeline-variant',
        title: '愛知県内の最新感染動向',
        date: Data.lastUpdate
      },
      newsItem: {
        date: '',
        url: 'https://www.pref.aichi.jp/site/covid19-aichi/',
        text: '愛知県発表の新型コロナウイルス感染症に関する情報はこちら'
      }
    }
    return data
  },
  head() {
    return {
      title: '県内の最新感染動向'
    }
  }
}
</script>

<style lang="scss" scoped>
.MainPage {
  .DataBlock {
    margin: 20px -8px;
    .DataCard {
      @include largerThan($medium) {
        padding: 10px;
      }
      @include lessThan($small) {
        padding: 4px 8px;
      }
    }
  }
}
</style>
