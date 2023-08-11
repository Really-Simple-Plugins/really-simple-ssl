import * as React from "react";
const SvgSvElSalvador = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SV_-_El_Salvador_svg__a"
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
    <g mask="url(#SV_-_El_Salvador_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="SV_-_El_Salvador_svg__b"
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
      <g mask="url(#SV_-_El_Salvador_svg__b)">
        <path
          fill="#3D58DB"
          fillRule="evenodd"
          d="M0 0v4h16V0H0ZM0 8v4h16V8H0Z"
          clipRule="evenodd"
        />
        <path
          stroke="#E8AA00"
          strokeWidth={0.5}
          d="M9.713 5.93a1.82 1.82 0 1 1-3.642 0 1.82 1.82 0 0 1 3.642 0Z"
        />
        <path
          fill="#1E601B"
          fillRule="evenodd"
          d="M6.905 4.831s-.476.784-.476 1.322S7 7.368 7.896 7.368c.875 0 1.504-.523 1.523-1.215.019-.692-.47-1.184-.47-1.184s.276.996.138 1.4c-.139.403-.587.891-1.19.83-.604-.063-1.177-.806-1.177-1.046s.185-1.322.185-1.322Z"
          clipRule="evenodd"
        />
        <path stroke="#188396" strokeWidth={0.5} d="M7.08 6.164h1.604" />
        <path
          stroke="#E8AA00"
          strokeWidth={0.5}
          d="M7.23 5.903h1.38M8.525 6.206H7.297l.62-1.029.608 1.029Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgSvElSalvador;
