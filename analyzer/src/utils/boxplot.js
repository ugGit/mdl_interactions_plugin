import HighCharts from "highcharts";

// generates an object to plot data from a user in a boxplot
export const generateSelectedUserPlotConfigData = (user, color) => {
  const pointCoordinates = Object.entries(user.data).map((point) => [
    parseInt(point[0]), // select x-axis
    point[1], // value on y-axis
  ]);
  return {
    name: user.name,
    color: color,
    type: "scatter",
    data: pointCoordinates,
    marker: {
      fillColor: color,
      lineWidth: 1,
      lineColor: color,
    },
    tooltip: {
      pointFormat: "Values {point.y}",
    },
  };
};

export const generateQuartileAndMedianPlotConfigData = (boxData) => {
  return {
    name: "Quartiles and Median",
    color: HighCharts.getOptions().colors[1],
    data: boxData,
    tooltip: {
      headerFormat: "<em>Category {point.key}</em><br/>",
    },
  };
};

export const generateOutlierPlotConfigData = (boxData) => {
  return {
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
  };
};
