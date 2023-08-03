import * as React from "react";
const SvgAuAustralia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="AU_-_Australia_svg__a"
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
    <g mask="url(#AU_-_Australia_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <g clipPath="url(#AU_-_Australia_svg__b)">
        <path fill="#2E42A5" d="M0 0h9v7H0z" />
        <path
          fill="#F7FCFF"
          d="m-1.002 6.5 1.98.869L9.045.944l1.045-1.29-2.118-.29-3.29 2.768-2.649 1.865L-1.002 6.5Z"
        />
        <path
          fill="#F50100"
          d="m-.731 7.108 1.009.505 9.437-8.08H8.298L-.731 7.109Z"
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
      <g fill="#F7FCFF" clipPath="url(#AU_-_Australia_svg__c)">
        <path d="m4.408 9.834-.59.546.058-.802-.795-.121.664-.455-.4-.697.768.236.295-.748.295.748.769-.236-.4.697.663.455-.795.121.058.802-.59-.546ZM10.776 6.069l-.394.364.04-.535-.53-.08.442-.304-.267-.464.512.157.197-.499.197.499.512-.157-.267.464.442.303-.53.081.04.535-.394-.364ZM11.715 2.377l-.394.363.04-.534-.53-.081.442-.303-.268-.465.513.157.197-.498.197.498.512-.157-.267.465.442.303-.53.08.04.535-.394-.363ZM14.061 4.223l-.393.364.039-.535-.53-.08.442-.304-.267-.464.513.157.196-.499.197.499.513-.157-.267.464.442.303-.53.081.039.535-.394-.364ZM12.184 9.53l-.394.364.04-.534-.53-.081.442-.303-.267-.465.512.157.197-.498.197.498.512-.157-.267.465.443.303-.53.08.039.535-.394-.363ZM13.827 6.648l-.4.21.076-.445-.323-.316.447-.065.2-.405.2.405.447.065-.324.316.077.445-.4-.21Z" />
      </g>
    </g>
    <defs>
      <clipPath id="AU_-_Australia_svg__b">
        <path fill="#fff" d="M0 0h9v7H0z" />
      </clipPath>
      <clipPath id="AU_-_Australia_svg__c">
        <path fill="#fff" d="M3 1h12v10H3z" />
      </clipPath>
    </defs>
  </svg>
);
export default SvgAuAustralia;
