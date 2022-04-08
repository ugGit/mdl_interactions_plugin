export const formatTimestampToDate = (timestamp) =>
  new Date(timestamp * 1000).toISOString().split("T")[0];

export const formatGrade = (grade) => grade.toFixed(2);
