function parseColor(input) {
    if (typeof input !== 'string') return null;

    const rgba = input.match(/^rgba?\(([^)]+)\)$/i);
    if (rgba) {
        const parts = rgba[1].split(',').map((s) => parseFloat(s.trim()));
        return { r: parts[0], g: parts[1], b: parts[2], a: parts[3] ?? 1 };
    }

    const hex = input.replace('#', '');
    if (hex.length === 3) {
        return {
            r: parseInt(hex[0] + hex[0], 16),
            g: parseInt(hex[1] + hex[1], 16),
            b: parseInt(hex[2] + hex[2], 16),
            a: 1,
        };
    }
    if (hex.length === 6) {
        return {
            r: parseInt(hex.slice(0, 2), 16),
            g: parseInt(hex.slice(2, 4), 16),
            b: parseInt(hex.slice(4, 6), 16),
            a: 1,
        };
    }
    return null;
}

function rgba(c, a) {
    return `rgba(${c.r}, ${c.g}, ${c.b}, ${a})`;
}

const themePlugin = {
    id: 'uppiChartTheme',
    beforeDatasetsDraw(chart) {
        const { ctx, chartArea } = chart;
        if (!chartArea) return;

        const datasets = chart.data?.datasets ?? [];
        datasets.forEach((dataset, index) => {
            const meta = chart.getDatasetMeta(index);
            const type = dataset.type ?? meta?.type ?? chart.config.type;

            if (type !== 'line' || dataset.fill === false) return;

            const colorSrc = dataset.borderColor ?? dataset.backgroundColor;
            const color = parseColor(colorSrc);
            if (!color) return;

            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            gradient.addColorStop(0, rgba(color, 0.28));
            gradient.addColorStop(1, rgba(color, 0));
            dataset.backgroundColor = gradient;

            if (dataset.fill === undefined || dataset.fill === null) {
                dataset.fill = 'origin';
            }
        });
    },
};

window.filamentChartJsPlugins = window.filamentChartJsPlugins ?? [];
if (!window.filamentChartJsPlugins.some((p) => p && p.id === themePlugin.id)) {
    window.filamentChartJsPlugins.push(themePlugin);
}
