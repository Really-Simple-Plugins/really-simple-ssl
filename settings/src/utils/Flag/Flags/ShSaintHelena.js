import * as React from "react";
const SvgShSaintHelena = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SH_-_Saint_Helena_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#SH_-_Saint_Helena_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <g clipPath="url(#SH_-_Saint_Helena_svg__b)">
        <path fill="#2E42A5" d="M0 0h9v7H0z" />
        <path
          fill="#F7FCFF"
          d="m-1.002 6.5 1.98.869L9.045.944l1.045-1.29-2.118-.29-3.29 2.768-2.649 1.865L-1.002 6.5Z"
        />
        <path
          fill="#F50100"
          d="m-.731 7.108 1.009.505 9.436-8.08H8.298L-.731 7.109Z"
        />
        <path
          fill="#F7FCFF"
          d="m10.002 6.5-1.98.869L-.045.944-1.09-.346l2.118-.29 3.29 2.768 2.649 1.865L10.002 6.5Z"
        />
        <path
          fill="#F50100"
          d="m9.935 6.937-1.01.504-4.018-3.46-1.19-.386L-1.19-.342H.227L5.13 3.502l1.303.463 3.502 2.972Z"
        />
        <path
          fill="#F50100"
          fillRule="evenodd"
          d="M4.992 0h-1v3H0v1h3.992v3h1V4H9V3H4.992V0Z"
          clipRule="evenodd"
        />
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="M3.242-.75h2.5v3H9.75v2.5H5.742v3h-2.5v-3H-.75v-2.5h3.992v-3ZM3.992 3H0v1h3.992v3h1V4H9V3H4.992V0h-1v3Z"
          clipRule="evenodd"
        />
      </g>
      <path
        fill="#B7E1FF"
        stroke="#F7FCFF"
        strokeWidth={0.25}
        d="M10.023 4.895h-.125v.125c0 .26-.001.515-.003.763-.002.592-.005 1.15.01 1.661.019.727.072 1.372.209 1.91.137.539.36.98.728 1.286.369.307.867.463 1.528.463.667 0 1.18-.194 1.564-.544.382-.348.626-.838.779-1.41.305-1.14.26-2.65.176-4.136l-.007-.118h-4.859Z"
      />
      <mask
        id="SH_-_Saint_Helena_svg__c"
        width={7}
        height={8}
        x={9}
        y={4}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          stroke="#fff"
          strokeWidth={0.25}
          d="M10.023 4.895h-.125v.125c0 .26-.001.515-.003.763-.002.592-.005 1.15.01 1.661.019.727.072 1.372.209 1.91.137.539.36.98.728 1.286.369.307.867.463 1.528.463.667 0 1.18-.194 1.564-.544.382-.348.626-.838.779-1.41.305-1.14.26-2.65.176-4.136l-.007-.118h-4.859Z"
        />
      </mask>
      <g mask="url(#SH_-_Saint_Helena_svg__c)">
        <path fill="#2E42A5" d="M11.2 9.8h3.6V11h-3.6z" />
        <g filter="url(#SH_-_Saint_Helena_svg__d)">
          <path fill="#FDFF00" d="M8.8 3.8H16v3.6H8.8z" />
        </g>
        <path
          fill="#272727"
          fillRule="evenodd"
          d="M12.4 7.04a.84.84 0 1 0 0-1.68.84.84 0 0 0 0 1.68Z"
          clipRule="evenodd"
        />
        <path
          fill="#CE6201"
          fillRule="evenodd"
          d="m10.07 8.412.537-.494s.724-.073.809.087c.085.16.034-.074.17.293.137.367.19-.157.266.367.075.525.138.706.138.828s.682.485.42.736c-.26.25-.273.827-.273.992 0 .165-.02.24-.285.202-.265-.037-.588.095-.652.095-.064 0-.842-.158-.842-.484 0-.326-.287-2.622-.287-2.622Z"
          clipRule="evenodd"
        />
      </g>
    </g>
    <defs>
      <clipPath id="SH_-_Saint_Helena_svg__b">
        <path fill="#fff" d="M0 0h9v7H0z" />
      </clipPath>
      <filter
        id="SH_-_Saint_Helena_svg__d"
        width={7.2}
        height={3.85}
        x={8.8}
        y={3.8}
        colorInterpolationFilters="sRGB"
        filterUnits="userSpaceOnUse"
      >
        <feFlood floodOpacity={0} result="BackgroundImageFix" />
        <feColorMatrix
          in="SourceAlpha"
          result="hardAlpha"
          values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
        />
        <feOffset dy={0.25} />
        <feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.5 0" />
        <feBlend
          in2="BackgroundImageFix"
          result="effect1_dropShadow_270_55072"
        />
        <feBlend
          in="SourceGraphic"
          in2="effect1_dropShadow_270_55072"
          result="shape"
        />
      </filter>
    </defs>
  </svg>
);
export default SvgShSaintHelena;
