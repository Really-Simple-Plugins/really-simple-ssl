import * as React from "react";
const SvgBwBotswana = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="BW_-_Botswana_svg__a"
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
    <g mask="url(#BW_-_Botswana_svg__a)">
      <path
        fill="#42ADDF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="BW_-_Botswana_svg__b"
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
      <g mask="url(#BW_-_Botswana_svg__b)">
        <path
          fill="#58A5FF"
          fillRule="evenodd"
          d="M0 0v4h16V0H0Z"
          clipRule="evenodd"
        />
        <path fill="#272727" stroke="#F7FCFF" d="M0 4.5h-.5v3h17v-3H0Z" />
      </g>
    </g>
  </svg>
);
export default SvgBwBotswana;
