<!DOCTYPE html>
<html>
<head>
<title>Chart Test</title>
<script src="js/chart.js"></script>
</head>
<body>
<canvas id="myChart" width="400" height="200"></canvas>
<script>
const ctx = document.getElementById('myChart');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: ['Jan', 'Feb', 'Mar'],
    datasets: [{
      label: 'Test Data',
      data: [10, 20, 15]
    }]
  }
});
</script>
</body>
</html>
