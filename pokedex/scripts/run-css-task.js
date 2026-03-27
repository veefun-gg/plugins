const tasks = require('../gulpfile');

const taskName = process.argv[2];
const task = tasks[taskName];

if (!taskName || typeof task !== 'function') {
  console.error(`Unknown CSS task: ${taskName || '(missing)'}`);
  process.exit(1);
}

try {
  const result = task();

  if (result && typeof result.on === 'function') {
    result.on('error', (error) => {
      console.error(error);
      process.exitCode = 1;
    });
  }
} catch (error) {
  console.error(error);
  process.exit(1);
}
