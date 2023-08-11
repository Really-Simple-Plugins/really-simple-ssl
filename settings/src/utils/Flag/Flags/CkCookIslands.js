import * as React from "react";
const SvgCkCookIslands = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="CK_-_Cook_Islands_svg__a"
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
    <g mask="url(#CK_-_Cook_Islands_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <g clipPath="url(#CK_-_Cook_Islands_svg__b)">
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
        stroke="#fff"
        d="M12.2 10.4a2.2 2.2 0 1 0 0-4.4 2.2 2.2 0 0 0 0 4.4Z"
        clipRule="evenodd"
      />
    </g>
    <defs>
      <clipPath id="CK_-_Cook_Islands_svg__b">
        <path fill="#fff" d="M0 0h9v7H0z" />
      </clipPath>
    </defs>
  </svg>
);
export default SvgCkCookIslands;
