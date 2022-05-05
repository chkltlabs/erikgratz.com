<template>
    <DxChart
        id="chart"
        :data-source="dataArr"
        :title="tickerName"
    >
        <DxLoadingIndicator
            :enabled="loading"
        />
        <DxCommonSeriesSettings
            argument-field="date"
            type="candlestick"
            color="#05FaB7"
        />
        <DxSeries
            :name="tickerName.substring(0, tickerName.indexOf('-'))"
            open-value-field="o"
            close-value-field="c"
            high-value-field="h"
            low-value-field="l"
        >
            <DxReduction color="#9333ea"/>
        </DxSeries>
        <DxArgumentAxis :workdays-only="false">
            <DxLabel format="shortDateShortTime"/>
        </DxArgumentAxis>
        <DxValueAxis :tick-interval="0">
            <DxTitle :text="tickerName.substring(1+tickerName.indexOf('-'))"/>
            <DxLabel>
                <DxFormat
                    :precision="precision"
                    type="currency"
                />
            </DxLabel>
        </DxValueAxis>
        <DxLegend item-text-position="right" position="inside"/>
        <DxExport :enabled="false"/>
        <DxTooltip
            :enabled="true"
            :customize-tooltip="customizeTooltip"
            location="edge"
        />
    </DxChart>
</template>
<script>

import DxChart, {
    DxCommonSeriesSettings,
    DxSeries,
    DxReduction,
    DxArgumentAxis,
    DxLabel,
    DxFormat,
    DxValueAxis,
    DxTitle,
    DxLegend,
    DxExport,
    DxTooltip,
    DxLoadingIndicator
} from 'devextreme-vue/chart';

export default {
    props: ['dataSource', 'latestData', 'tickerName', 'precision', 'loading'],
    components: {
        DxChart,
        DxCommonSeriesSettings,
        DxSeries,
        DxReduction,
        DxArgumentAxis,
        DxLabel,
        DxFormat,
        DxValueAxis,
        DxTitle,
        DxLegend,
        DxExport,
        DxTooltip,
        DxLoadingIndicator
    },
    // data() {
    //     return {
    //         tickerName,
    //     };
    // },
    methods: {
        customizeTooltip(pointInfo) {
            return {
                text: `Time: $${pointInfo.argumentText}<br/>
Open: $${pointInfo.openValue}<br/>
Close: $${pointInfo.closeValue}<br/>
High: $${pointInfo.highValue}<br/>
Low: $${pointInfo.lowValue}<br/>`,
            };
        },
    },
    computed:{
        dataArr(){
            return [this.latestData, ...this.dataSource]
        }
    }
};
</script>
<style>
#chart {
    height: 440px;
}
</style>
