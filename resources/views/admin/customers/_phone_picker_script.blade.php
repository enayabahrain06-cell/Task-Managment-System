<script>
if (!window.PHONE_COUNTRIES) {
    window.PHONE_COUNTRIES = [
        {code:'AF',flag:'🇦🇫',name:'Afghanistan',     dial:'+93'},
        {code:'AL',flag:'🇦🇱',name:'Albania',          dial:'+355'},
        {code:'DZ',flag:'🇩🇿',name:'Algeria',          dial:'+213'},
        {code:'AD',flag:'🇦🇩',name:'Andorra',          dial:'+376'},
        {code:'AO',flag:'🇦🇴',name:'Angola',           dial:'+244'},
        {code:'AR',flag:'🇦🇷',name:'Argentina',        dial:'+54'},
        {code:'AM',flag:'🇦🇲',name:'Armenia',          dial:'+374'},
        {code:'AU',flag:'🇦🇺',name:'Australia',        dial:'+61'},
        {code:'AT',flag:'🇦🇹',name:'Austria',          dial:'+43'},
        {code:'AZ',flag:'🇦🇿',name:'Azerbaijan',       dial:'+994'},
        {code:'BH',flag:'🇧🇭',name:'Bahrain',          dial:'+973'},
        {code:'BD',flag:'🇧🇩',name:'Bangladesh',       dial:'+880'},
        {code:'BY',flag:'🇧🇾',name:'Belarus',          dial:'+375'},
        {code:'BE',flag:'🇧🇪',name:'Belgium',          dial:'+32'},
        {code:'BO',flag:'🇧🇴',name:'Bolivia',          dial:'+591'},
        {code:'BA',flag:'🇧🇦',name:'Bosnia',           dial:'+387'},
        {code:'BR',flag:'🇧🇷',name:'Brazil',           dial:'+55'},
        {code:'BG',flag:'🇧🇬',name:'Bulgaria',         dial:'+359'},
        {code:'KH',flag:'🇰🇭',name:'Cambodia',         dial:'+855'},
        {code:'CM',flag:'🇨🇲',name:'Cameroon',         dial:'+237'},
        {code:'CA',flag:'🇨🇦',name:'Canada',           dial:'+1'},
        {code:'CL',flag:'🇨🇱',name:'Chile',            dial:'+56'},
        {code:'CN',flag:'🇨🇳',name:'China',            dial:'+86'},
        {code:'CO',flag:'🇨🇴',name:'Colombia',         dial:'+57'},
        {code:'CR',flag:'🇨🇷',name:'Costa Rica',       dial:'+506'},
        {code:'HR',flag:'🇭🇷',name:'Croatia',          dial:'+385'},
        {code:'CU',flag:'🇨🇺',name:'Cuba',             dial:'+53'},
        {code:'CY',flag:'🇨🇾',name:'Cyprus',           dial:'+357'},
        {code:'CZ',flag:'🇨🇿',name:'Czech Republic',   dial:'+420'},
        {code:'DK',flag:'🇩🇰',name:'Denmark',          dial:'+45'},
        {code:'EC',flag:'🇪🇨',name:'Ecuador',          dial:'+593'},
        {code:'EG',flag:'🇪🇬',name:'Egypt',            dial:'+20'},
        {code:'SV',flag:'🇸🇻',name:'El Salvador',      dial:'+503'},
        {code:'EE',flag:'🇪🇪',name:'Estonia',          dial:'+372'},
        {code:'ET',flag:'🇪🇹',name:'Ethiopia',         dial:'+251'},
        {code:'FI',flag:'🇫🇮',name:'Finland',          dial:'+358'},
        {code:'FR',flag:'🇫🇷',name:'France',           dial:'+33'},
        {code:'GE',flag:'🇬🇪',name:'Georgia',          dial:'+995'},
        {code:'DE',flag:'🇩🇪',name:'Germany',          dial:'+49'},
        {code:'GH',flag:'🇬🇭',name:'Ghana',            dial:'+233'},
        {code:'GR',flag:'🇬🇷',name:'Greece',           dial:'+30'},
        {code:'GT',flag:'🇬🇹',name:'Guatemala',        dial:'+502'},
        {code:'HN',flag:'🇭🇳',name:'Honduras',         dial:'+504'},
        {code:'HK',flag:'🇭🇰',name:'Hong Kong',        dial:'+852'},
        {code:'HU',flag:'🇭🇺',name:'Hungary',          dial:'+36'},
        {code:'IN',flag:'🇮🇳',name:'India',            dial:'+91'},
        {code:'ID',flag:'🇮🇩',name:'Indonesia',        dial:'+62'},
        {code:'IR',flag:'🇮🇷',name:'Iran',             dial:'+98'},
        {code:'IQ',flag:'🇮🇶',name:'Iraq',             dial:'+964'},
        {code:'IE',flag:'🇮🇪',name:'Ireland',          dial:'+353'},
        {code:'IL',flag:'🇮🇱',name:'Israel',           dial:'+972'},
        {code:'IT',flag:'🇮🇹',name:'Italy',            dial:'+39'},
        {code:'JP',flag:'🇯🇵',name:'Japan',            dial:'+81'},
        {code:'JO',flag:'🇯🇴',name:'Jordan',           dial:'+962'},
        {code:'KZ',flag:'🇰🇿',name:'Kazakhstan',       dial:'+7'},
        {code:'KE',flag:'🇰🇪',name:'Kenya',            dial:'+254'},
        {code:'KW',flag:'🇰🇼',name:'Kuwait',           dial:'+965'},
        {code:'KG',flag:'🇰🇬',name:'Kyrgyzstan',       dial:'+996'},
        {code:'LV',flag:'🇱🇻',name:'Latvia',           dial:'+371'},
        {code:'LB',flag:'🇱🇧',name:'Lebanon',          dial:'+961'},
        {code:'LY',flag:'🇱🇾',name:'Libya',            dial:'+218'},
        {code:'LT',flag:'🇱🇹',name:'Lithuania',        dial:'+370'},
        {code:'LU',flag:'🇱🇺',name:'Luxembourg',       dial:'+352'},
        {code:'MY',flag:'🇲🇾',name:'Malaysia',         dial:'+60'},
        {code:'MV',flag:'🇲🇻',name:'Maldives',         dial:'+960'},
        {code:'MT',flag:'🇲🇹',name:'Malta',            dial:'+356'},
        {code:'MX',flag:'🇲🇽',name:'Mexico',           dial:'+52'},
        {code:'MD',flag:'🇲🇩',name:'Moldova',          dial:'+373'},
        {code:'MN',flag:'🇲🇳',name:'Mongolia',         dial:'+976'},
        {code:'ME',flag:'🇲🇪',name:'Montenegro',       dial:'+382'},
        {code:'MA',flag:'🇲🇦',name:'Morocco',          dial:'+212'},
        {code:'MZ',flag:'🇲🇿',name:'Mozambique',       dial:'+258'},
        {code:'MM',flag:'🇲🇲',name:'Myanmar',          dial:'+95'},
        {code:'NP',flag:'🇳🇵',name:'Nepal',            dial:'+977'},
        {code:'NL',flag:'🇳🇱',name:'Netherlands',      dial:'+31'},
        {code:'NZ',flag:'🇳🇿',name:'New Zealand',      dial:'+64'},
        {code:'NI',flag:'🇳🇮',name:'Nicaragua',        dial:'+505'},
        {code:'NG',flag:'🇳🇬',name:'Nigeria',          dial:'+234'},
        {code:'NO',flag:'🇳🇴',name:'Norway',           dial:'+47'},
        {code:'OM',flag:'🇴🇲',name:'Oman',             dial:'+968'},
        {code:'PK',flag:'🇵🇰',name:'Pakistan',         dial:'+92'},
        {code:'PS',flag:'🇵🇸',name:'Palestine',        dial:'+970'},
        {code:'PA',flag:'🇵🇦',name:'Panama',           dial:'+507'},
        {code:'PY',flag:'🇵🇾',name:'Paraguay',         dial:'+595'},
        {code:'PE',flag:'🇵🇪',name:'Peru',             dial:'+51'},
        {code:'PH',flag:'🇵🇭',name:'Philippines',      dial:'+63'},
        {code:'PL',flag:'🇵🇱',name:'Poland',           dial:'+48'},
        {code:'PT',flag:'🇵🇹',name:'Portugal',         dial:'+351'},
        {code:'QA',flag:'🇶🇦',name:'Qatar',            dial:'+974'},
        {code:'RO',flag:'🇷🇴',name:'Romania',          dial:'+40'},
        {code:'RU',flag:'🇷🇺',name:'Russia',           dial:'+7'},
        {code:'SA',flag:'🇸🇦',name:'Saudi Arabia',     dial:'+966'},
        {code:'SN',flag:'🇸🇳',name:'Senegal',          dial:'+221'},
        {code:'RS',flag:'🇷🇸',name:'Serbia',           dial:'+381'},
        {code:'SG',flag:'🇸🇬',name:'Singapore',        dial:'+65'},
        {code:'SK',flag:'🇸🇰',name:'Slovakia',         dial:'+421'},
        {code:'SI',flag:'🇸🇮',name:'Slovenia',         dial:'+386'},
        {code:'SO',flag:'🇸🇴',name:'Somalia',          dial:'+252'},
        {code:'ZA',flag:'🇿🇦',name:'South Africa',     dial:'+27'},
        {code:'KR',flag:'🇰🇷',name:'South Korea',      dial:'+82'},
        {code:'ES',flag:'🇪🇸',name:'Spain',            dial:'+34'},
        {code:'LK',flag:'🇱🇰',name:'Sri Lanka',        dial:'+94'},
        {code:'SD',flag:'🇸🇩',name:'Sudan',            dial:'+249'},
        {code:'SE',flag:'🇸🇪',name:'Sweden',           dial:'+46'},
        {code:'CH',flag:'🇨🇭',name:'Switzerland',      dial:'+41'},
        {code:'SY',flag:'🇸🇾',name:'Syria',            dial:'+963'},
        {code:'TW',flag:'🇹🇼',name:'Taiwan',           dial:'+886'},
        {code:'TJ',flag:'🇹🇯',name:'Tajikistan',       dial:'+992'},
        {code:'TZ',flag:'🇹🇿',name:'Tanzania',         dial:'+255'},
        {code:'TH',flag:'🇹🇭',name:'Thailand',         dial:'+66'},
        {code:'TN',flag:'🇹🇳',name:'Tunisia',          dial:'+216'},
        {code:'TR',flag:'🇹🇷',name:'Turkey',           dial:'+90'},
        {code:'TM',flag:'🇹🇲',name:'Turkmenistan',     dial:'+993'},
        {code:'AE',flag:'🇦🇪',name:'UAE',              dial:'+971'},
        {code:'GB',flag:'🇬🇧',name:'UK',               dial:'+44'},
        {code:'UA',flag:'🇺🇦',name:'Ukraine',          dial:'+380'},
        {code:'UG',flag:'🇺🇬',name:'Uganda',           dial:'+256'},
        {code:'US',flag:'🇺🇸',name:'United States',    dial:'+1'},
        {code:'UY',flag:'🇺🇾',name:'Uruguay',          dial:'+598'},
        {code:'UZ',flag:'🇺🇿',name:'Uzbekistan',       dial:'+998'},
        {code:'VE',flag:'🇻🇪',name:'Venezuela',        dial:'+58'},
        {code:'VN',flag:'🇻🇳',name:'Vietnam',          dial:'+84'},
        {code:'YE',flag:'🇾🇪',name:'Yemen',            dial:'+967'},
        {code:'ZM',flag:'🇿🇲',name:'Zambia',           dial:'+260'},
        {code:'ZW',flag:'🇿🇼',name:'Zimbabwe',         dial:'+263'},
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
        get filtered() {
            const q = this.search.toLowerCase().replace('+','').trim();
            if (!q) return window.PHONE_COUNTRIES;
            return window.PHONE_COUNTRIES.filter(c =>
                c.name.toLowerCase().includes(q) ||
                c.dial.replace('+','').startsWith(q)
            );
        },
        get grouped() {
            const list = this.filtered;
            if (this.search.trim()) return [{ letter: '', items: list }];
            const map = {};
            for (const c of list) {
                const l = c.name[0].toUpperCase();
                if (!map[l]) map[l] = [];
                map[l].push(c);
            }
            return Object.keys(map).sort().map(l => ({ letter: l, items: map[l] }));
        },
        get full() {
            const n = this.local.replace(/[\s\-\(\)]/g,'');
            return n ? this.selected.dial + n : '';
        },
        pick(c) { this.selected = c; this.search = ''; this.open = false; },
    };
};
</script>
