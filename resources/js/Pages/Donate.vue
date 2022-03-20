<template>
    <div class="flex flex-row justify-center items-center">
        <img src="storage/images/webp/face.webp"
             class="shadow rounded-full h-32 align-middle border-none transition duration-1000 transform hover:scale-150"/>
        <div class="flex flex-col justify-center items-center mt-16 md:mt-8 m-8 p-4 border border-purple-600 text-center">
            <p> Hey, no pressure, but if you like what you see here, or you use what I built, </p>
            <p> or you just like me, I would love a donation. </p>
            <p> Alternatively, donate to a charity or bail fund noted below or in your area.</p>
            <p> Fighting for the working class is our work and this is how we do it. </p>
            <p>If you can't spare it, no worries! Sharing on socials helps out a ton as well. </p>
            <p> Whatever you choose, I'm glad you read this, and I love you. </p>
            <p> Thank you very much. </p>
        </div>
    </div>
    <div class="flex flex-col md:flex-row justify-center items-center">
        <div class="flex flex-col justify-center items-center m-8 p-4 border border-purple-600">
            <QRCodeElement
                v-for="(value, name) in walletCodes"
                :code-value="value"
                :code-size="size"
                :title="name"
                :icon="iconSvgPaths[name]"
                description="This is a placeholder description"
            />
        </div>
        <div class="flex flex-col justify-center items-center m-8 p-4 border border-purple-600">
            <CharityButton
                v-for="(value, name) in charities"
                :link="value.donateLink"
                :icon="value.imageLink"
                :blurb="value.blurb"
                :alt-text="name"
            />
        </div>
        <div class="flex flex-col justify-center items-center m-8 p-4 border border-purple-600">
            <SocialButton
                v-for="(value, name) in socials"
                :link="value.link"
                :icon="value.iconSvg"
                :alt-text="value.altText"
                :name="name"
            />
        </div>
    </div>
</template>
<script>
import QRCodeElement from '../Components/QRCodeElement'
import SocialButton from "@/Components/SocialButton";
import CharityButton from "@/Components/CharityButton";

export default {
    components: {CharityButton, SocialButton, QRCodeElement},
    props: ['errors', 'auth', 'walletCodes', 'iconSvgPaths', 'socials', 'charities'],
    data() {
        return {
            size: 75,
            // walletCodes: {
            //     ethereum: 'ethereum',
            //     btc: 'bitcoin',
            //     btcash: 'bitcoin cash',
            // }
        }
    }
}
</script>
