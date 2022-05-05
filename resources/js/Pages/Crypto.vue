<template>
    <div class="h-max">
        <DxTabs
            :items="tickers" v-model:selected-item="ticker"/>
        <div class="flex items-center justify-center grid grid-cols-12">
            <div class="col-span-10 h-full">
                <CandlestickChart
                    :data-source="mappedKlines"
                    :latest-data="latestCandleObject"
                    :ticker-name="ticker"
                    :precision="precision"
                    :loading="loading"
                />
            </div>
            <div class="col-span-2 h-full">
                <OrdersTicker
                    :ticker="tickerData"
                />
            </div>
        </div>
        <DxTabs
            :items="timescaleOptions" v-model:selected-item="timescale"/>
    </div>
</template>
<script>

//todo - wire up symbol and timeframe selectors to redraw data
// todo continues - data is available after axios call, parsing and redrawing the
// todo continues - chart is difficult. Need to re-do data structure
//todo - get order book data, format and display it (pass to OrdersTicker component)
//todo - replicate on backend, design algorithm for analysis


import CandlestickChart from "@/Components/CandlestickChart";
import OrdersTicker from "@/Components/OrdersTicker";
import {DxTabPanel, DxItem} from 'devextreme-vue/tab-panel';
import DxTabs from "devextreme-vue/tabs";
import 'devextreme/dist/css/dx.material.teal.dark.compact.css';

export default {
    components: {OrdersTicker, CandlestickChart, DxTabPanel, DxItem, DxTabs},
    props: ['res', 'ticker', 'timescale', 'klines'],
    // layout: false,
    data() {
        return {
            precision: 0,
            loading: false,
            logCount: 0,
            response: JSON.parse(this.res),
            endpointData: null,
            latestCandleObject: {},
            socket: null,
            socketID: null,
            pingInterval: null,
            pongInterval: null,
            tickerSelectedIndex: 0,
            tickerData: {},
            candleData: {},
            candleTopics: {},
            timeScaleSelectedIndex: 4,
            timescaleOptions: [
                '1min', '3min', '15min',
                '30min', '1hour', '2hour',
                '4hour', '6hour', '8hour',
                '12hour', '1day', '1week'
            ],
            tickers: [
                'BTC-USDT', 'ETH-USDT', 'ETH-BTC',
            ],
            turnOff: false,
        }
    },
    methods: {
        onWelcome(data) {
            // console.log(data.id)
            this.socketID = data.id
            //set up keep-alive ping
            this.pingInterval = setInterval(function (socket, socketID) {
                socket.send(JSON.stringify({'id': socketID, 'type': 'ping'}))
            }, this.endpointData.pingInterval, this.socket, this.socketID)
            this.subscribe()
        },
        onPong() {
            clearInterval(this.pongInterval)
            this.pongInterval = setInterval(function () {
                console.log('pong not recieved')
                this.open()
                //todo ne? - implement auto-refreshing connection logic. test both same-page and full refresh solutions
            }, this.endpointData.pingInterval * 2)
        },
        onAck() {
            //console.log('ack confirmed')
        },
        onMessage(data) {
            // this.latestData = data
            if (data.subject === 'trade.ticker') {
                this.tickerData = data.data
            }
            if (data.subject === 'trade.candles.update') {
                const dataa = data.data.candles
                this.latestCandleObject = {
                    date: new Date(parseInt(dataa[0]) * 1000),
                    o: parseFloat(dataa[1]),
                    c: parseFloat(dataa[2]),
                    h: parseFloat(dataa[3]),
                    l: parseFloat(dataa[4]),
                    vol: parseFloat(dataa[5]),
                    amt: parseFloat(dataa[6])
                }
            }
        },
        loadCandleData(data) {
            // const sym = data.symbol
            const dateInSecs = data[0]
            this.precision = parseFloat(data[1]).countDecimals() //the precision of the float in data[1]
            this.candleData[dateInSecs + ''] = {
                date: new Date(parseInt(dateInSecs) * 1000),
                o: parseFloat(data[1]),
                c: parseFloat(data[2]),
                h: parseFloat(data[3]),
                l: parseFloat(data[4]),
                vol: parseFloat(data[5]),
                amt: parseFloat(data[6])
            }
        },
        open() {
            this.endpointData = this.response.data.instanceServers[0];
            this.socket = new WebSocket(this.endpointData.endpoint + '?token=' + this.response.data.token);
            this.socket.onmessage = function (event) {
                let data = JSON.parse(event.data)
                this.log(data)
                switch (data.type) {
                    case 'welcome':
                        this.onWelcome(data)
                        break;
                    case 'pong':
                        this.onPong()
                        break;
                    case 'ack':
                        this.onAck()
                        break;
                    case 'message':
                        this.onMessage(data)
                        break;
                    default:
                        console.log(event.data)

                }
            }.bind(this)
        },
        unsubscribe(topic) {
            this.socket.send(JSON.stringify({
                "id": this.socketID,
                "type": "unsubscribe",
                "topic": topic,
                "response": false
            }))
        },
        subscribe() {
            this.subscribeCandles()
            this.subscribeTicker()
            this.subscribeOrders()
        },
        subscribeCandles() {
            const prospectiveTopic = "/market/candles:" + this.ticker + '_' + this.timescale
            this.socket.send(JSON.stringify({
                "id": this.socketID,
                "type": "subscribe",
                "topic": prospectiveTopic,
                "response": false
            }))
        },
        subscribeTicker() {
            this.socket.send(JSON.stringify({
                "id": this.socketID,
                "type": "subscribe",
                "topic": "/market/ticker:" + this.ticker,
                "response": true
            }))
        },
        // subscribeOrders() {
        //     this.socket.send(JSON.stringify({
        //         "id": this.socketID,
        //         "type": "subscribe",
        //         "topic": "/spotMarket/level2Depth5:" + this.ticker,
        //         "response": true
        //     }))
        // },
        parseHistoricCandles() {
            // Object.keys(this.klines)?.forEach(key => {
            //     Object.values(this.klines[key].data)?.forEach(arr => {
            //         this.loadCandleData(key, arr)
            //     })
            // })
        },
        redraw() {
            //     this.timescales[this.tickerSelected] = this.timeScaleSelected
            //     console.log('redraw hit')
            //     this.loading = true
            //     window.axios
            //         .get('/crypto/klines/' + this.timeScaleSelected + '/' + this.tickerSelected)
            //         .then((response) => {
            //             this.candleData[this.tickerSelected] = {}
            //             this.klines[this.tickerSelected] = response
            //             this.parseHistoricCandles()
            //             this.loading = false
            //         })
            //         .catch(function (error) {
            //             console.log(error)
            //             this.loading = false
            //         })
            // this.parseHistoricCandles()
        },

        log(data) {
            //common message types and subjects to exclude from logging
            const types = ['welcome', 'pong', 'ack',];
            const subjects = ['trade.ticker'];
            if (!types.includes(data.type) && this.logCount < 4) {
                if ('subject' in data) {
                    if (!subjects.includes(data.subject)) {
                        console.log(data)
                        this.logCount++
                    }
                    if (data.subject === 'trade.candles.update') {
                        console.log(data)
                        this.logCount++
                    }
                }
            }
        }
    },
    computed: {
        mappedKlines() {
            return this.klines?.data?.map(data => {
                return {
                    date: new Date(parseInt(data[0]) * 1000),
                    o: parseFloat(data[1]),
                    c: parseFloat(data[2]),
                    h: parseFloat(data[3]),
                    l: parseFloat(data[4]),
                    vol: parseFloat(data[5]),
                    amt: parseFloat(data[6])
                }
            })
        }
    },
    created() {
        this.parseHistoricCandles()
        if (this.turnOff) return;
        this.open()
    },
    watch: {
        ticker() {
            this.$inertia.visit('/crypto/' + this.ticker + '/' + this.timescale, {
                preserveState: true,
            })
        },
        timescale() {
            this.$inertia.visit('/crypto/' + this.ticker + '/' + this.timescale, {
                preserveState: true,
            })
        },
    }
}
</script>
