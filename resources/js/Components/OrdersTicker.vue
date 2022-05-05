<template>
    <div class="border-2 border-dopegray h-full">
        <div class="border-2 border-bottom border-dopegray" v-for="askpair in sortedAsks">
            asks: {{ askpair }}
        </div>
        <div class="border-2 border-bottom border-dopegray">
            last sale: {{ ticker }}
        </div>
        <div class="border-2 border-bottom border-dopegray" v-for="bidpair in sortedBids">
            bids: {{ bidpair }}
        </div>
    </div>
</template>
<script>
//todo - format data to be prettyful

export default {
    props: ['ticker'],
    data() {
        return {
            orders: {
                asks: [
                    ["9989", "8"],    //price, size
                    ["9990", "32"],
                    ["9991", "47"],
                    ["9992", "3"],
                    ["9993", "3"]
                ],
                bids: [
                    ["9988", "56"],
                    ["9987", "15"],
                    ["9986", "100"],
                    ["9985", "10"],
                    ["9984", "10"]
                ],
            },
            tickerData: {}
        }
    },
    computed:{
        sortedAsks() {
            return this.orders.asks.sort(function(a, b) {
                return b[0] - a[0];
            }).map(function(arr){
                return arr.map(function(item){
                    return parseFloat(item)
                })
            });
        },
        sortedBids() {
            return this.orders.bids.sort(function(a, b){
                return b[0] - a[0];
            }).map(function(arr){
                return arr.map(function(item){
                    return parseFloat(item)
                })
            });
        }
    },
    created() {
        // const res = window.axios.get('')
    }
}
</script>
