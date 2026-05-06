<script>
if (!window.PHONE_COUNTRIES) {
    window.PHONE_COUNTRIES = [
        /* Priority: Middle East */
        {code:'AE',flag:'🇦🇪',name:'UAE',          dial:'+971'},
        {code:'SA',flag:'🇸🇦',name:'Saudi Arabia',  dial:'+966'},
        {code:'KW',flag:'🇰🇼',name:'Kuwait',        dial:'+965'},
        {code:'BH',flag:'🇧🇭',name:'Bahrain',       dial:'+973'},
        {code:'QA',flag:'🇶🇦',name:'Qatar',         dial:'+974'},
        {code:'OM',flag:'🇴🇲',name:'Oman',          dial:'+968'},
        {code:'JO',flag:'🇯🇴',name:'Jordan',        dial:'+962'},
        {code:'LB',flag:'🇱🇧',name:'Lebanon',       dial:'+961'},
        {code:'EG',flag:'🇪🇬',name:'Egypt',         dial:'+20'},
        {code:'IQ',flag:'🇮🇶',name:'Iraq',          dial:'+964'},
        {code:'SY',flag:'🇸🇾',name:'Syria',         dial:'+963'},
        {code:'YE',flag:'🇾🇪',name:'Yemen',         dial:'+967'},
        {code:'PS',flag:'🇵🇸',name:'Palestine',     dial:'+970'},
        {code:'MA',flag:'🇲🇦',name:'Morocco',       dial:'+212'},
        {code:'TN',flag:'🇹🇳',name:'Tunisia',       dial:'+216'},
        {code:'LY',flag:'🇱🇾',name:'Libya',         dial:'+218'},
        {code:'SD',flag:'🇸🇩',name:'Sudan',         dial:'+249'},
        /* Rest of world */
        {code:'US',flag:'🇺🇸',name:'United States', dial:'+1'},
        {code:'GB',flag:'🇬🇧',name:'UK',            dial:'+44'},
        {code:'IN',flag:'🇮🇳',name:'India',         dial:'+91'},
        {code:'PK',flag:'🇵🇰',name:'Pakistan',      dial:'+92'},
        {code:'PH',flag:'🇵🇭',name:'Philippines',   dial:'+63'},
        {code:'BD',flag:'🇧🇩',name:'Bangladesh',    dial:'+880'},
        {code:'LK',flag:'🇱🇰',name:'Sri Lanka',     dial:'+94'},
        {code:'NP',flag:'🇳🇵',name:'Nepal',         dial:'+977'},
        {code:'ID',flag:'🇮🇩',name:'Indonesia',     dial:'+62'},
        {code:'MY',flag:'🇲🇾',name:'Malaysia',      dial:'+60'},
        {code:'SG',flag:'🇸🇬',name:'Singapore',     dial:'+65'},
        {code:'TH',flag:'🇹🇭',name:'Thailand',      dial:'+66'},
        {code:'VN',flag:'🇻🇳',name:'Vietnam',       dial:'+84'},
        {code:'CN',flag:'🇨🇳',name:'China',         dial:'+86'},
        {code:'JP',flag:'🇯🇵',name:'Japan',         dial:'+81'},
        {code:'KR',flag:'🇰🇷',name:'South Korea',   dial:'+82'},
        {code:'TR',flag:'🇹🇷',name:'Turkey',        dial:'+90'},
        {code:'IR',flag:'🇮🇷',name:'Iran',          dial:'+98'},
        {code:'NG',flag:'🇳🇬',name:'Nigeria',       dial:'+234'},
        {code:'GH',flag:'🇬🇭',name:'Ghana',         dial:'+233'},
        {code:'KE',flag:'🇰🇪',name:'Kenya',         dial:'+254'},
        {code:'ET',flag:'🇪🇹',name:'Ethiopia',      dial:'+251'},
        {code:'ZA',flag:'🇿🇦',name:'South Africa',  dial:'+27'},
        {code:'FR',flag:'🇫🇷',name:'France',        dial:'+33'},
        {code:'DE',flag:'🇩🇪',name:'Germany',       dial:'+49'},
        {code:'IT',flag:'🇮🇹',name:'Italy',         dial:'+39'},
        {code:'ES',flag:'🇪🇸',name:'Spain',         dial:'+34'},
        {code:'RU',flag:'🇷🇺',name:'Russia',        dial:'+7'},
        {code:'UA',flag:'🇺🇦',name:'Ukraine',       dial:'+380'},
        {code:'BR',flag:'🇧🇷',name:'Brazil',        dial:'+55'},
        {code:'MX',flag:'🇲🇽',name:'Mexico',        dial:'+52'},
        {code:'CA',flag:'🇨🇦',name:'Canada',        dial:'+1'},
        {code:'AU',flag:'🇦🇺',name:'Australia',     dial:'+61'},
    ];
}

if (!window.parsePhoneNumber) {
    window.parsePhoneNumber = function(full) {
        if (!full) return { dial: '+971', local: '' };
        const sorted = [...window.PHONE_COUNTRIES].sort((a, b) => b.dial.length - a.dial.length);
        for (const c of sorted) {
            if (full.startsWith(c.dial)) return { dial: c.dial, local: full.slice(c.dial.length).trim() };
        }
        return { dial: '+971', local: full };
    };
}

window.phonePicker = function phonePicker(initialFull) {
    const p = window.parsePhoneNumber(initialFull || '');
    const initial = window.PHONE_COUNTRIES.find(c => c.dial === p.dial) || window.PHONE_COUNTRIES[0];
    return {
        selected: initial,
        local:    p.local,
        search:   '',
        open:     false,
        dropTop:  0,
        dropLeft: 0,
        dropW:    260,
        get filtered() {
            const q = this.search.toLowerCase();
            return q ? window.PHONE_COUNTRIES.filter(c => c.name.toLowerCase().includes(q) || c.dial.includes(q)) : window.PHONE_COUNTRIES;
        },
        get full() {
            const n = this.local.replace(/[\s\-\(\)]/g,'');
            return n ? this.selected.dial + n : '';
        },
        toggle(btn) {
            if (!this.open) {
                const r = btn.getBoundingClientRect();
                this.dropTop  = r.bottom + 4;
                this.dropLeft = Math.max(4, r.left);
                // Keep within viewport
                const overflow = this.dropLeft + this.dropW - window.innerWidth + 8;
                if (overflow > 0) this.dropLeft -= overflow;
            }
            this.open = !this.open;
        },
        pick(c) { this.selected = c; this.search = ''; this.open = false; },
    };
};
</script>
