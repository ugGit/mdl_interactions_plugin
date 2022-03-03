<template>
  <div>asd</div>

  <div id="plotlyChart"></div>
</template>

<script>
import * as Plotly from "plotly.js-dist";

export default {
  name: "PlotlyChart",
  props: ["data", "layout"],
  mounted() {
    const targetDiv = document.getElementById("plotlyChart");

    Plotly.newPlot(targetDiv, this.data, this.layout);
  },
};
</script>
