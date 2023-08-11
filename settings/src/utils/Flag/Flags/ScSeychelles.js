import * as React from "react";
const SvgScSeychelles = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SC_-_Seychelles_svg__a"
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
    <g mask="url(#SC_-_Seychelles_svg__a)">
      <path
        fill="#2E42A5"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="SC_-_Seychelles_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#SC_-_Seychelles_svg__b)">
        <path
          fill="#FFD018"
          fillRule="evenodd"
          d="M0 11.998 8.076-1h8.075L0 11.998Z"
          clipRule="evenodd"
        />
        <path fill="#E31D1C" d="M0 11.998 17.232 5.5v-8.05L0 11.998Z" />
        <path fill="#F7FCFF" d="M0 11.998 17.232 9.5V5.45L0 11.998Z" />
        <path
          fill="#5EAA22"
          fillRule="evenodd"
          d="M0 11.998h17.232v-3.55L0 11.998Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgScSeychelles;
