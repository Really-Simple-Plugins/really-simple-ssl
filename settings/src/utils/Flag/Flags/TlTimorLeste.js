import * as React from "react";
const SvgTlTimorLeste = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TL_-_Timor-Leste_svg__a"
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
    <g mask="url(#TL_-_Timor-Leste_svg__a)">
      <path fill="#E31D1C" stroke="#F7FCFF" d="M0-.5h-.5v13h17v-13H0Z" />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="m0 0 12 6-12 6V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#272727"
        fillRule="evenodd"
        d="m0 0 8 6-8 6V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="m3.324 7.204-1.01 1.05-.105-1.492L.92 5.968l1.343-.421.22-1.48.93 1.172 1.355-.363-.7 1.387.681 1.339-1.426-.398Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgTlTimorLeste;
