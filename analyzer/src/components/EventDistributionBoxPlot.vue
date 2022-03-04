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
  props: ["data"],
  components: {
    VueHighcharts,
  },
  computed: {
    // inspired by: https://stackoverflow.com/a/30896483/6691953
    chartOptions() {
      //build the data and add the series to the chart
      const boxData = [],
        meanData = [];

      // get labels from input data (i.e. categories)
      const labels = Object.keys(this.data[Object.keys(this.data)[0]]);
      // transform input data to arrays containing values of each category
      const data = labels.map((l) => {
        return Object.values(this.data).map((user) => {
          return user[l];
        });
      });
      // calculate box plot values
      for (let i = 0; i < data.length; i++) {
        const boxValues = this.getBoxValues(data[i]);
        boxValues.values.x = i;
        const boxOutliers = this.getOutliers(
          data[i],
          boxValues.values.low,
          boxValues.values.high
        );
        boxValues.values.outliers = boxOutliers.map((x) => [i, x]); // TODO: place this properly
        boxData.push(boxValues.values);
        meanData.push([i, this.mean(data)]);
      }

      return {
        chart: {
          type: "boxplot",
        },

        title: {
          text: "",
        },

        legend: {
          enabled: false,
        },

        xAxis: {
          categories: labels,
          title: {
            text: "Categories",
          },
        },

        series: [
          {
            name: "Events per Category",
            data: boxData,
            tooltip: {
              headerFormat: "<em>Category {point.key}</em><br/>",
            },
          },
          {
            name: "Outliers",
            color: HighCharts.getOptions().colors[0],
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
              lineColor: HighCharts.getOptions().colors[0],
            },
            tooltip: {
              enabled: false,
              pointFormat: "Value: {point.y}",
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

    //get the mean of an array of numbers
    mean(data) {
      var len = data.length;
      var sum = 0;
      for (var i = 0; i < len; i++) {
        sum += parseFloat(data[i]);
      }
      return sum / len;
    },

    //because .sort() doesn't sort numbers correctly
    numSort(a, b) {
      return a - b;
    },
  },
};
</script>
