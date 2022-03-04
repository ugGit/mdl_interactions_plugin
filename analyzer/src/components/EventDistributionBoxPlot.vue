<template>
  <v-card>
    <v-card-header>Overall Event Distribution</v-card-header>
    <vue-highcharts
      type="chart"
      :options="chartOptions"
      :redrawOnUpdate="true"
      :oneToOneUpdate="true"
      :animateOnUpdate="true"
    />
  </v-card>
</template>

<script>
import VueHighcharts from "vue3-highcharts";
import HighCharts from "highcharts";
import HighchartsMore from "highcharts/highcharts-more";

HighchartsMore(HighCharts);

export default {
  name: "EventDistributionBoxPlot",
  props: ["data", "selectedUsers"],
  components: {
    VueHighcharts,
  },
  computed: {
    // inspired by: https://stackoverflow.com/a/30896483/6691953
    chartOptions() {
      //build the data and add the series to the chart
      const boxData = [];

      // get labels from input data (i.e. categories)
      const labels = Object.keys(this.data[Object.keys(this.data)[0]]);
      // transform input data to arrays containing values of each category
      const transformedData = labels.map((l) => {
        return Object.values(this.data).map((user) => {
          return user[l];
        });
      });
      // calculate box plot values
      for (let i = 0; i < transformedData.length; i++) {
        const boxValues = this.getBoxValues(transformedData[i]);
        boxValues.values.x = i;
        const boxOutliers = this.getOutliers(
          transformedData[i],
          boxValues.values.low,
          boxValues.values.high
        );
        boxValues.values.outliers = boxOutliers.map((x) => [i, x]); // TODO: place this properly
        boxData.push(boxValues.values);
      }

      return {
        chart: {
          type: "boxplot",
        },

        title: {
          text: "",
        },

        legend: {
          verticalAlign: "top",
          layout: "horizontal",
        },

        xAxis: {
          categories: labels,
          title: {
            text: "Categories",
          },
        },

        series: [
          {
            name: "Quartiles and Median",
            color: HighCharts.getOptions().colors[1],
            data: boxData,
            tooltip: {
              headerFormat: "<em>Category {point.key}</em><br/>",
            },
          },
          {
            name: "Outliers",
            color: HighCharts.getOptions().colors[1],
            type: "scatter",
            data: [
              // x, y positions where x=0 is the first category
              ...Array.prototype.concat(
                // unpack  to meet expected format from highcharts
                ...boxData
                  .filter((d) => d.outliers.length > 0) // ignore empty arrays
                  .map((d) => d.outliers)
              ),
            ],
            marker: {
              fillColor: "white",
              lineWidth: 1,
              lineColor: HighCharts.getOptions().colors[1],
            },
            tooltip: {
              pointFormat: "Value: {point.y}",
            },
          },
          {
            name: this.selectedUsers[0].name,
            color: HighCharts.getOptions().colors[0],
            type: "scatter",
            data: Object.entries(this.selectedUsers[0].data).map((point) => [
              parseInt(point[0]),
              point[1],
            ]),
            marker: {
              fillColor: HighCharts.getOptions().colors[0],
              lineWidth: 1,
              lineColor: HighCharts.getOptions().colors[0],
            },
            tooltip: {
              pointFormat: "Values {point.y}",
            },
          },
          {
            name: this.selectedUsers[1].name,
            color: HighCharts.getOptions().colors[3],
            type: "scatter",
            data: Object.entries(this.selectedUsers[1].data).map((point) => [
              parseInt(point[0]),
              point[1],
            ]),
            marker: {
              fillColor: HighCharts.getOptions().colors[3],
              lineWidth: 1,
              lineColor: HighCharts.getOptions().colors[3],
            },
            tooltip: {
              pointFormat: "Values {point.y}",
            },
          },
        ],
      };
    },
  },
  methods: {
    getOutliers(data, lowerFence, upperFence) {
      const outliers = [];
      for (var i = 0; i < data.length; i++) {
        if (data[i] < lowerFence || data[i] > upperFence) {
          outliers.push(data[i]);
        }
      }
      return outliers;
    },

    getBoxValues(data) {
      var boxData = {},
        min = Math.min.apply(Math, data),
        max = Math.max.apply(Math, data),
        q1 = this.getPercentile(data, 25),
        median = this.getPercentile(data, 50),
        q3 = this.getPercentile(data, 75),
        iqr = q3 - q1,
        lowerFence = q1 - iqr * 1.5,
        upperFence = q3 + iqr * 1.5;

      boxData.values = {};
      boxData.values.low = min < lowerFence ? lowerFence : min;
      boxData.values.q1 = q1;
      boxData.values.median = median;
      boxData.values.q3 = q3;
      boxData.values.high = max > upperFence ? upperFence : max;
      return boxData;
    },

    //get any percentile from an array
    getPercentile(data, percentile) {
      data.sort(this.numSort);
      var index = (percentile / 100) * data.length;
      var result;
      if (Math.floor(index) == index) {
        result = (data[index - 1] + data[index]) / 2;
      } else {
        result = data[Math.floor(index)];
      }
      return result;
    },

    //because .sort() doesn't sort numbers correctly
    numSort(a, b) {
      return a - b;
    },
  },
};
</script>
