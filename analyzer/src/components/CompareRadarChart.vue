<template>
  <vue-highcharts
    type="chart"
    :options="chartOptions"
    :redrawOnUpdate="true"
    :oneToOneUpdate="false"
    :animateOnUpdate="true"
  />
</template>

<script>
import VueHighcharts from "vue3-highcharts";
import HighCharts from "highcharts";
import HighchartsMore from "highcharts/highcharts-more";

HighchartsMore(HighCharts);

export default {
  name: "CompareRadarChart",
  props: ["plotData"],
  components: {
    VueHighcharts,
  },
  data: function () {
    return {};
  },
  computed: {
    chartOptions() {
      const series = [];
      this.plotData.forEach((element) => {
        series.push({
          name: element.name,
          data: element.data,
          pointPlacement: "on",
        });
      });

      return {
        chart: {
          polar: true,
          type: "line",
        },

        title: {
          text: "Event Distribution",
          x: -80,
        },

        pane: {
          size: "80%",
        },

        xAxis: {
          categories: [
            "Sales",
            "Marketing",
            "Development",
            "Customer Support",
            "Information Technology",
            "Administration",
          ],
          tickmarkPlacement: "on",
          lineWidth: 0,
        },

        yAxis: {
          gridLineInterpolation: "polygon",
          lineWidth: 0,
          min: 0,
        },

        tooltip: {
          shared: true,
          pointFormat:
            '<span style="color:{series.color}">{series.name}: <b>{point.y:,.0f}</b><br/>',
        },

        legend: {
          align: "right",
          verticalAlign: "middle",
          layout: "vertical",
        },

        series: series,

        responsive: {
          rules: [
            {
              condition: {
                maxWidth: 500,
              },
              chartOptions: {
                legend: {
                  align: "center",
                  verticalAlign: "bottom",
                  layout: "horizontal",
                },
                pane: {
                  size: "70%",
                },
              },
            },
          ],
        },
      };
    },
  },
};
</script>
