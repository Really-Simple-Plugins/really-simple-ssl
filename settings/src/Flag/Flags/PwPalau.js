import * as React from "react";
const SvgPwPalau = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="PW_-_Palau_svg__a"
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
    <g mask="url(#PW_-_Palau_svg__a)">
      <path
        fill="#61C6F0"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="PW_-_Palau_svg__b"
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
      <g mask="url(#PW_-_Palau_svg__b)">
        <path
          fill="#FBCD17"
          fillRule="evenodd"
          d="M5.75 9a3.25 3.25 0 1 0 0-6.5 3.25 3.25 0 0 0 0 6.5Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgPwPalau;
